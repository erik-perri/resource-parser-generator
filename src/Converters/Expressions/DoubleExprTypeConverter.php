<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\DNumber;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class DoubleExprTypeConverter implements ExprTypeConverterContract
{
    public function convert(DNumber $expr, ConverterContext $context): TypeContract
    {
        return new Types\FloatType();
    }
}
