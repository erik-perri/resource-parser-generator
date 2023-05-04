<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\PhpFileParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class ExpressionTypeConverter
{
    public function __construct(
        private readonly ClassFileLocatorContract $classLocator,
        private readonly PhpFileParser $fileParser,
    ) {
        //
    }

    public function convert(Expr $expr, ResolverContract $resolver): TypeContract
    {
        if ($expr instanceof MethodCall) {
            return $this->extractTypeFromMethodCall($expr, $resolver);
        }

        if ($expr instanceof NullsafeMethodCall) {
            return $this->extractTypeFromNullsafeMethodCall($expr, $resolver);
        }

        if ($expr instanceof Ternary) {
            return $this->extractTypeFromTernary($expr, $resolver);
        }

        if ($expr instanceof Variable) {
            $name = $expr->name;
            if ($name instanceof Expr) {
                throw new RuntimeException('Unexpected expression in variable name');
            }

            if ($name === 'this') {
                $thisType = $resolver->resolveThis();
                if (!$thisType) {
                    throw new RuntimeException('Unable to resolve $this');
                }
                return new Types\ClassType($thisType, null);
            }

            $variableType = $resolver->resolveVariable($name);

            if (!$variableType) {
                throw new RuntimeException(sprintf('Cannot resolve variable "%s"', $name));
            }

            return $variableType;
        }

        if ($expr instanceof PropertyFetch) {
            return $this->extractTypeFromPropertyFetch($expr, $resolver);
        }

        if ($expr instanceof ConstFetch) {
            switch ($expr->name->toLowerString()) {
                case 'true':
                case 'false':
                    return new Types\BoolType();
                case 'null':
                    return new Types\NullType();
            }

            throw new RuntimeException(sprintf('Unhandled constant name "%s"', $expr->name));
        }

        if ($expr instanceof UnaryMinus ||
            $expr instanceof UnaryPlus ||
            $expr instanceof LNumber) {
            return new Types\IntType();
        }

        if ($expr instanceof DNumber) {
            return new Types\FloatType();
        }

        if ($expr instanceof String_) {
            return new Types\StringType();
        }

        throw new RuntimeException(sprintf('Unhandled expression type "%s"', $expr->getType()));
    }

    private function extractTypeFromMethodCall(MethodCall $value, ResolverContract $resolver): TypeContract
    {
        $leftSide = $this->getLeftSideScope($value, $resolver);
        $rightSide = $this->getRightSide($value, $resolver);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of method call is not a string');
        }

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" for right side for method call', $rightSide[0]),
            );
        }

        return $methodScope->returnType();
    }

    private function extractTypeFromNullsafeMethodCall(
        NullsafeMethodCall $value,
        ResolverContract $resolver
    ): TypeContract {
        $leftSide = $this->getLeftSideScope($value, $resolver);
        $rightSide = $this->getRightSide($value, $resolver);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of method call is not a string');
        }

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" for right side for method call', $rightSide[0]),
            );
        }

        $return = $methodScope->returnType();

        if ($return instanceof Types\UnionType) {
            $return = $return->addToUnion(new Types\NullType());
        } else {
            $return = new Types\UnionType($return, new Types\NullType());
        }

        return $return;
    }

    private function extractTypeFromPropertyFetch(PropertyFetch $value, ResolverContract $resolver): TypeContract
    {
        $leftSide = $this->getLeftSideScope($value, $resolver);
        $rightSide = $this->getRightSide($value, $resolver);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of property fetch is not a string');
        }

        $type = $leftSide->propertyType($rightSide);
        if (!$type) {
            throw new RuntimeException(
                sprintf('Unknown property "%s" for right side for property fetch', $rightSide[0]),
            );
        }

        return $type;
    }

    private function extractTypeFromTernary(Ternary $value, ResolverContract $resolver): TypeContract
    {
        if (!$value->if) {
            throw new RuntimeException('Ternary expression missing if');
        }

        $ifType = $this->convert($value->if, $resolver);
        $elseType = $this->convert($value->else, $resolver);

        if ($ifType->name() === $elseType->name()) {
            return $ifType;
        }

        return new Types\UnionType($ifType, $elseType);
    }

    private function getLeftSideScope(
        PropertyFetch|MethodCall|NullsafeMethodCall $value,
        ResolverContract $resolver,
    ): ClassScope {
        $leftSide = $this->convert($value->var, $resolver);

        if ($value instanceof NullsafeMethodCall) {
            if (!($leftSide instanceof Types\UnionType)) {
                throw new RuntimeException(
                    sprintf('Unexpected left side %s, "%s"', $value->name, $leftSide->name()),
                );
            }

            $leftTypes = $leftSide->types()->filter(fn(TypeContract $type) => !($type instanceof Types\NullType));
            if ($leftTypes->count() !== 1) {
                throw new RuntimeException(
                    sprintf('Unexpected left side %s, "%s"', $value->name, $leftSide->name()),
                );
            }

            $leftSide = $leftTypes->first();
        }

        if (!($leftSide instanceof Types\ClassType)) {
            throw new RuntimeException(
                sprintf('Left side %s is not a class type', $value->name),
            );
        }

        $leftSideFile = $this->classLocator->get($leftSide->name());
        $leftSideFileScope = $this->fileParser->parse(File::get($leftSideFile));
        $leftSideClassScope = $leftSideFileScope->classes()->first();
        if (!$leftSideClassScope) {
            throw new RuntimeException(
                sprintf('Unknown class "%s" for left side %s of method call', $leftSide->name(), $value->name),
            );
        }

        return $leftSideClassScope;
    }

    private function getRightSide(
        PropertyFetch|MethodCall|NullsafeMethodCall $value,
        ResolverContract $resolver,
    ): TypeContract|string {
        return $value->name instanceof Expr
            ? $this->convert($value->name, $resolver)
            : $value->name->name;
    }
}
