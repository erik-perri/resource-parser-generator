<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;

trait ParsesFetchSides
{
    abstract protected function expressionTypeConverter(): ExpressionTypeConverterContract;

    abstract protected function classParser(): ClassParserContract;

    private function convertLeftSideToType(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
    ): TypeContract {
        $leftSide = $this->expressionTypeConverter()->convert($expr->var, $context);

        if ($expr instanceof NullsafePropertyFetch || $expr instanceof NullsafeMethodCall) {
            $leftSide = $this->removeNullableFromUnion($expr, $leftSide);
        }

        return $leftSide;
    }

    private function convertLeftSideToClassScope(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
    ): ClassScopeContract {
        $leftSide = $this->convertLeftSideToType($expr, $context);

        if (!($leftSide instanceof ClassType)) {
            throw new RuntimeException(
                sprintf('Left side "%s" of fetch is not a class type, found "%s"', $expr->name, $leftSide->describe()),
            );
        }

        return $this->classParser()->parseType($leftSide);
    }

    private function removeNullableFromUnion(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        TypeContract $type,
    ): TypeContract {
        if (!($type instanceof UnionType)) {
            throw new RuntimeException(sprintf(
                'Left side of "%s" fetch not union as expected, instead found "%s"',
                $expr->name,
                $type->describe(),
            ));
        }

        $type = $type->removeNullable();
        if ($type instanceof UnionType) {
            throw new RuntimeException(sprintf(
                'Left side of "%s" fetch not single-union as expected, instead found "%s"',
                $expr->name,
                $type->describe(),
            ));
        }

        return $type;
    }

    private function convertRightSide(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
    ): TypeContract|string {
        return $expr->name instanceof Expr
            ? $this->expressionTypeConverter()->convert($expr->name, $context)
            : $expr->name->name;
    }
}
