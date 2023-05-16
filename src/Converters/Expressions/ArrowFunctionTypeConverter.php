<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ArrowFunction;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;

class ArrowFunctionTypeConverter implements TypeConverterContract
{
    public function __construct(private readonly ExprTypeConverterContract $exprTypeConverter)
    {
        //
    }

    public function convert(ArrowFunction $expr, ConverterContext $context): TypeContract
    {
        return $this->exprTypeConverter->convert($expr->expr, $context);
    }
}
