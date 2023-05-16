<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Converters\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;

trait ParsesFetchSides
{
    abstract protected function exprTypeConverter(): ExprTypeConverterContract;

    abstract protected function classParser(): ClassParserContract;

    private function convertLeftSideToClassScope(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
    ): ClassScopeContract {
        $leftSide = $this->exprTypeConverter()->convert($expr->var, $context);

        if ($expr instanceof NullsafePropertyFetch || $expr instanceof NullsafeMethodCall) {
            if (!($leftSide instanceof UnionType)) {
                throw new RuntimeException(sprintf(
                    'Left side of "%s" fetch not union as expected, instead found "%s"',
                    $expr->name,
                    $leftSide->describe(),
                ));
            }

            $leftSide = $leftSide->removeNullable();
            if ($leftSide instanceof UnionType) {
                throw new RuntimeException(sprintf(
                    'Left side of "%s" fetch not single-union as expected, instead found "%s"',
                    $expr->name,
                    $leftSide->describe(),
                ));
            }
        }

        if (!($leftSide instanceof ClassType)) {
            throw new RuntimeException(
                sprintf('Left side "%s" of fetch is not a class type, found "%s"', $expr->name, $leftSide->describe()),
            );
        }

        return $this->classParser()->parse($leftSide->fullyQualifiedName());
    }

    private function convertRightSide(
        PropertyFetch|NullsafePropertyFetch|MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
    ): TypeContract|string {
        return $expr->name instanceof Expr
            ? $this->exprTypeConverter()->convert($expr->name, $context)
            : $expr->name->name;
    }
}
