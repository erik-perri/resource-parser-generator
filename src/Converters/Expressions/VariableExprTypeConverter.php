<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;
use RuntimeException;

class VariableExprTypeConverter implements ExprTypeConverterContract
{
    public function convert(Variable $expr, ConverterContext $context): TypeContract
    {
        $name = $expr->name;
        if ($name instanceof Expr) {
            throw new RuntimeException('Unexpected expression in variable name');
        }

        if ($name === 'this') {
            $thisType = $context->resolver()->resolveThis();
            if (!$thisType) {
                throw new RuntimeException('Unable to resolve $this');
            }
            return new Types\ClassType($thisType, null);
        }

        $variableType = $context->resolver()->resolveVariable($name);

        if (!$variableType) {
            throw new RuntimeException(sprintf('Cannot resolve variable "%s"', $name));
        }

        return $variableType;
    }
}
