<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters\Expressions;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;

/**
 * @method TypeContract convert(Expr $expr, ConverterContext $context)
 */
interface TypeConverterContract
{
    //
}
