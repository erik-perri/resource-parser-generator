<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use Illuminate\Http\Resources\MissingValue;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Parsers\ClassMethodReturnParser;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;
use Sourcetoad\EnhancedResources\Resource;

class ExpressionTypeConverter
{
    public function __construct(
        private readonly DeclaredTypeConverter $declaredTypeConverter,
        private readonly ClassParser $classParser,
        private readonly ResolverContract $resolver,
        private readonly ClassMethodReturnParser|null $methodReturnParser,
    ) {
        //
    }

    public static function create(
        ResolverContract $resolver,
        ClassMethodReturnParser|null $methodReturnParser,
    ): self {
        return resolve(self::class, [
            'resolver' => $resolver,
            'methodReturnParser' => $methodReturnParser,
        ]);
    }

    public function convert(Expr $expr): TypeContract
    {
        if ($expr instanceof MethodCall) {
            return $this->extractTypeFromMethodCall($expr);
        }

        if ($expr instanceof NullsafeMethodCall) {
            return $this->extractTypeFromNullsafeMethodCall($expr);
        }

        if ($expr instanceof Ternary) {
            return $this->extractTypeFromTernary($expr);
        }

        if ($expr instanceof Variable) {
            $name = $expr->name;
            if ($name instanceof Expr) {
                throw new RuntimeException('Unexpected expression in variable name');
            }

            if ($name === 'this') {
                $thisType = $this->resolver->resolveThis();
                if (!$thisType) {
                    throw new RuntimeException('Unable to resolve $this');
                }
                return new Types\ClassType($thisType, null);
            }

            $variableType = $this->resolver->resolveVariable($name);

            if (!$variableType) {
                throw new RuntimeException(sprintf('Cannot resolve variable "%s"', $name));
            }

            return $variableType;
        }

        if ($expr instanceof PropertyFetch) {
            return $this->extractTypeFromPropertyFetch($expr);
        }

        if ($expr instanceof NullsafePropertyFetch) {
            return $this->extractTypeFromNullsafePropertyFetch($expr);
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

        if ($expr instanceof ArrowFunction) {
            return $this->convert($expr->expr);
        }

        if ($expr instanceof StaticCall) {
            return $this->extractTypeFromStaticCall($expr);
        }

        if ($expr instanceof ClassConstFetch) {
            return $this->extractTypeFromClassConstFetch($expr);
        }

        throw new RuntimeException(sprintf('Unhandled expression type "%s"', $expr->getType()));
    }

    private function extractTypeFromMethodCall(MethodCall $value): TypeContract
    {
        $leftSide = $this->getLeftSideScope($value);
        $rightSide = $this->getRightSide($value);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of method call is not a string');
        }

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" for right side for method call', $rightSide[0]),
            );
        }

        $returnType = $methodScope->returnType();

        if ($returnType instanceof Types\ClassType &&
            $returnType->fullyQualifiedName() === $leftSide->fullyQualifiedName() &&
            $leftSide->hasParent(Resource::class)) {
            $formatMethod = $this->locateFormatMethodInResourceCall($leftSide, $value)
                ?? $this->locateDefaultFormatMethodInResource($leftSide);

            if (!$formatMethod) {
                throw new RuntimeException(
                    sprintf('Unable to determine format format of resource "%s"', $leftSide->fullyQualifiedName()),
                );
            }

            if (!$this->methodReturnParser) {
                throw new RuntimeException('Unable to parse method return');
            }

            return $this->methodReturnParser->parse($leftSide->fullyQualifiedName(), $formatMethod);
        }

        if ($rightSide === 'whenLoaded') {
            if (!($returnType instanceof Types\UnionType)) {
                throw new RuntimeException('Unexpected non-union whenLoaded method return');
            }

            $args = $value->getArgs();
            if (count($args) < 2) {
                throw new RuntimeException('Unhandled missing second argument for whenLoaded');
            }

            $returnWhenLoaded = $this->convert($args[1]->value);

            $returnWhenUnloaded = count($args) > 2
                ? $this->convert($args[2]->value)
                : new Types\UndefinedType();

            $returnType = $returnType
                ->addToUnion($returnWhenLoaded)
                ->addToUnion($returnWhenUnloaded)
                ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\MixedType)
                ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\ClassType
                    && $type->fullyQualifiedName() === MissingValue::class);
        }

        return $returnType;
    }

    private function extractTypeFromNullsafeMethodCall(
        NullsafeMethodCall $value
    ): TypeContract {
        $leftSide = $this->getLeftSideScope($value);
        $rightSide = $this->getRightSide($value);

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

    private function extractTypeFromNullsafePropertyFetch(NullsafePropertyFetch $value): TypeContract
    {
        $leftSide = $this->getLeftSideScope($value);
        $rightSide = $this->getRightSide($value);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of property fetch is not a string');
        }

        $type = $leftSide->propertyType($rightSide);
        if (!$type) {
            throw new RuntimeException(
                sprintf('Unknown property "%s" for right side for property fetch', $rightSide[0]),
            );
        }

        if ($type instanceof Types\UnionType) {
            $type = $type->addToUnion(new Types\NullType());
        } else {
            $type = new Types\UnionType($type, new Types\NullType());
        }

        return $type;
    }

    private function extractTypeFromPropertyFetch(PropertyFetch $value): TypeContract
    {
        $leftSide = $this->getLeftSideScope($value);
        $rightSide = $this->getRightSide($value);

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

    private function extractTypeFromTernary(Ternary $value): TypeContract
    {
        if (!$value->if) {
            throw new RuntimeException('Ternary expression missing if');
        }

        $ifType = $this->convert($value->if);
        $elseType = $this->convert($value->else);

        if ($ifType->describe() === $elseType->describe()) {
            return $ifType;
        }

        return new Types\UnionType($ifType, $elseType);
    }

    private function getLeftSideScope(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $value,
    ): ClassScopeContract {
        $leftSide = $this->convert($value->var);

        if ($value instanceof NullsafePropertyFetch || $value instanceof NullsafeMethodCall) {
            if (!($leftSide instanceof Types\UnionType)) {
                throw new RuntimeException(
                    sprintf('Unexpected left side %s, "%s"', $value->name, $leftSide->describe()),
                );
            }

            $leftTypes = $leftSide->types()->filter(fn(TypeContract $type) => !($type instanceof Types\NullType));
            if ($leftTypes->count() !== 1) {
                throw new RuntimeException(
                    sprintf('Unexpected left side %s, "%s"', $value->name, $leftSide->describe()),
                );
            }

            $leftSide = $leftTypes->first();
        }

        if (!($leftSide instanceof Types\ClassType)) {
            throw new RuntimeException(
                sprintf('Left side %s is not a class type', $value->name),
            );
        }

        return $this->classParser->parse($leftSide->fullyQualifiedName());
    }

    private function getRightSide(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $value,
    ): TypeContract|string {
        return $value->name instanceof Expr
            ? $this->convert($value->name)
            : $value->name->name;
    }

    private function extractTypeFromStaticCall(StaticCall $expr): TypeContract
    {
        $class = $expr->class instanceof Expr
            ? $this->convert($expr->class)
            : $this->declaredTypeConverter->convert($expr->class, $this->resolver);

        if (!($class instanceof Types\ClassType)) {
            throw new RuntimeException('Static call class is not a class type');
        }

        $classScope = $this->classParser->parse($class->fullyQualifiedName());
        $methodName = $expr->name;
        if ($methodName instanceof Expr) {
            throw new RuntimeException('Static call name is not a string');
        }

        $methodScope = $classScope->method($methodName->name);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" for class "%s"', $methodName->name, $class->fullyQualifiedName()),
            );
        }

        return $methodScope->returnType();
    }

    private function extractTypeFromClassConstFetch(ClassConstFetch $expr): TypeContract
    {
        $class = $expr->class instanceof Expr
            ? $this->convert($expr->class)
            : $this->declaredTypeConverter->convert($expr->class, $this->resolver);

        if (!($class instanceof Types\ClassType)) {
            throw new RuntimeException('Class const fetch class is not a class type');
        }

        $classScope = $this->classParser->parse($class->fullyQualifiedName());
        $constName = $expr->name;
        if ($constName instanceof Expr) {
            throw new RuntimeException('Class const fetch name is not a string');
        }

        $constScope = $classScope->constant($constName->name);
        if (!$constScope) {
            throw new RuntimeException(
                sprintf('Unknown constant "%s" for class "%s"', $constName->name, $class->fullyQualifiedName()),
            );
        }

        return $constScope->type();
    }

    private function locateFormatMethodInResourceCall(ClassScopeContract $resourceClass, MethodCall $call): string|null
    {
        if ($call->name instanceof Expr) {
            throw new RuntimeException('Method call name is not a string');
        }

        if ($call->name->toString() === 'format') {
            $formatArg = $call->getArgs()[0]->value;

            if ($formatArg instanceof String_) {
                return $formatArg->value;
            } elseif ($formatArg instanceof ClassConstFetch) {
                if ($formatArg->class instanceof Expr) {
                    throw new RuntimeException('Class const fetch class is not a string');
                }

                $fetchClassName = $this->resolver->resolveClass($formatArg->class->toString());
                if (!$fetchClassName) {
                    throw new RuntimeException(
                        sprintf('Unknown class "%s" for class const fetch', $formatArg->class->toString()),
                    );
                }

                $fetchClass = $fetchClassName === $resourceClass->fullyQualifiedName()
                    ? $resourceClass
                    : $this->classParser->parse($fetchClassName);

                if ($formatArg->name instanceof Expr\Error) {
                    throw new RuntimeException('Class const fetch name is not a string');
                }

                $constName = $formatArg->name->toString();
                $constScope = $fetchClass->constant($constName);

                if (!$constScope) {
                    throw new RuntimeException(
                        sprintf('Unknown constant "%s" for class "%s"', $constName, $fetchClass->fullyQualifiedName()),
                    );
                }

                // TODO Return method with Format attribute rather than method name
                return strval($constScope->value());
            }
        }

        // TODO Parse format call nested in chain

        return null;
    }

    // @phpstan-ignore-next-line
    private function locateDefaultFormatMethodInResource(ClassScopeContract $resourceClass): string|null
    {
        // TODO Find method with IsDefault attribute or fail
        return null;
    }
}
