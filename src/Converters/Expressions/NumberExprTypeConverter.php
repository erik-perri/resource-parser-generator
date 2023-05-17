<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Scalar\LNumber;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;

class NumberExprTypeConverter implements ExprTypeConverterContract
{
    public function convert(UnaryMinus|UnaryPlus|LNumber $expr, ConverterContext $context): TypeContract
    {
        return new Types\IntType();
    }
}