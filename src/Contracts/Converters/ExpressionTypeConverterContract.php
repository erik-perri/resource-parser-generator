<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ExpressionTypeConverterContract
{
    public function convert(Expr $expr, ConverterContext $context): TypeContract;
}
