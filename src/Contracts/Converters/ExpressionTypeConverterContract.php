<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;

interface ExpressionTypeConverterContract
{
    public function convert(Expr $expr, ConverterContext $context): TypeContract;
}
