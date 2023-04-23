<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

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
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\FileParser;
use ResourceParserGenerator\Parsers\PhpParser\Context\ResolverContract;

class ExpressionToTypeConverter
{
    public function __construct(
        private readonly ClassFileFinder $classFileFinder,
        private readonly FileParser $fileParser,
    ) {
        //
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    public function convert(Expr $expr, ResolverContract $resolver): array
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
            if ($expr->name instanceof Expr) {
                throw new ParseResultException('Unexpected expression in variable name', $expr);
            }

            return $resolver->resolveVariable($expr->name);
        }

        if ($expr instanceof PropertyFetch) {
            return $this->extractTypeFromPropertyFetch($expr, $resolver);
        }

        if ($expr instanceof ConstFetch) {
            switch ($expr->name->toLowerString()) {
                case 'true':
                case 'false':
                    return ['bool'];
                case 'null':
                    return ['null'];
            }

            throw new ParseResultException('Unhandled constant name "' . $expr->name . '"', $expr);
        }

        if ($expr instanceof UnaryMinus ||
            $expr instanceof UnaryPlus ||
            $expr instanceof LNumber) {
            return ['int'];
        }

        if ($expr instanceof DNumber) {
            return ['float'];
        }

        if ($expr instanceof String_) {
            return ['string'];
        }

        throw new ParseResultException('Unhandled expression type "' . $expr->getType() . '"', $expr);
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromPropertyFetch(PropertyFetch $value, ResolverContract $resolver): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resolver);

        /**
         * @var class-string $leftSideClass
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $leftSideClass = $leftSide[0];
        $leftSideFile = $this->classFileFinder->find($leftSideClass);
        $leftSideFileScope = $this->fileParser->parse($leftSideFile);
        $leftSideClassScope = $leftSideFileScope->class($leftSideClass);
        if (!$leftSideClassScope) {
            throw new ParseResultException(
                'Unknown class "' . $leftSideClass . '" for left side of property fetch',
                $value->var,
            );
        }

        $types = $leftSideClassScope->propertyTypes($rightSide[0]);
        if (!$types) {
            throw new ParseResultException(
                'Unknown type of "' . $rightSide[0] . '" for right side of property fetch',
                $value->var,
            );
        }

        return $types;
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromMethodCall(MethodCall $value, ResolverContract $resolver): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resolver);

        /**
         * @var class-string $leftSideClass
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $leftSideClass = $leftSide[0];
        $leftSideFile = $this->classFileFinder->find($leftSideClass);
        $leftSideFileScope = $this->fileParser->parse($leftSideFile);
        $leftSideClassScope = $leftSideFileScope->class($leftSideClass);
        if (!$leftSideClassScope) {
            throw new ParseResultException(
                'Unknown class "' . $leftSideClass . '" for left side of method call',
                $value->var,
            );
        }

        $methodScope = $leftSideClassScope->method($rightSide[0]);
        if (!$methodScope) {
            throw new ParseResultException(
                'Unknown method "' . $rightSide[0] . '" for right side for method call',
                $value->var,
            );
        }

        return $methodScope->returnTypes();
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromNullsafeMethodCall(NullsafeMethodCall $value, ResolverContract $resolver): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resolver);

        /**
         * @var class-string $leftSideClass
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $leftSideClass = $leftSide[0];
        $leftSideFile = $this->classFileFinder->find($leftSideClass);
        $leftSideFileScope = $this->fileParser->parse($leftSideFile);
        $leftSideClassScope = $leftSideFileScope->class($leftSideClass);
        if (!$leftSideClassScope) {
            throw new ParseResultException(
                'Unknown class "' . $leftSideClass . '" for left side of nullsafe method call',
                $value->var,
            );
        }

        $methodScope = $leftSideClassScope->method($rightSide[0]);
        if (!$methodScope) {
            throw new ParseResultException(
                'Unknown method "' . $rightSide[0] . '" for right side of nullsafe method call',
                $value->var,
            );
        }

        $types = $methodScope->returnTypes();

        return array_unique(array_merge($types, ['null']));
    }

    /**
     * @return array<int, string[]>
     * @throws ParseResultException|ReflectionException
     */
    public function extractSides(
        PropertyFetch|MethodCall|NullsafeMethodCall $value,
        ResolverContract $resolver,
    ): array {
        $leftSide = $this->convert($value->var, $resolver);
        if ($value instanceof NullsafeMethodCall) {
            $leftSide = array_filter($leftSide, fn($type) => $type !== 'null');
        }
        if (count($leftSide) !== 1) {
            throw new ParseResultException(
                'Unexpected compound left side of property fetch',
                $value->var,
            );
        }

        $rightSide = $value->name instanceof Expr
            ? $this->convert($value->name, $resolver)
            : [$value->name->name];
        if (count($rightSide) !== 1) {
            throw new ParseResultException(
                'Unexpected compound right side of property fetch',
                $value->var,
            );
        }

        return [$leftSide, $rightSide];
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromTernary(Ternary $value, ResolverContract $resolver): array
    {
        if (!$value->if) {
            throw new ParseResultException('Ternary expression missing if', $value);
        }

        $ifType = $this->convert($value->if, $resolver);
        $elseType = $this->convert($value->else, $resolver);

        return array_unique(array_merge($ifType, $elseType));
    }
}
