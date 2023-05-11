<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters\Expressions;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

/**
 * @method TypeContract convert(Expr $expr, ResolverContract $resolver)
 */
interface TypeConverterContract
{
    //
}
