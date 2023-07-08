<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Cast\String_ as CastString_;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class StringExprTypeConverter implements ExprTypeConverterContract
{
    public function convert(String_|CastString_ $expr, ConverterContext $context): TypeContract
    {
        return new Types\StringType();
    }
}
