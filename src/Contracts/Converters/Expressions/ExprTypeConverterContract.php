<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters\Expressions;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Types\TypeContract;

/**
 * @method TypeContract convert(Expr $expr, ConverterContext $context)
 */
interface ExprTypeConverterContract
{
    //
}
