<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ArrowFunction;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;

class ArrowFunctionExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(private readonly ExpressionTypeConverterContract $expressionTypeConverter)
    {
        //
    }

    public function convert(ArrowFunction $expr, ConverterContext $context): TypeContract
    {
        return $this->expressionTypeConverter->convert($expr->expr, $context);
    }
}
