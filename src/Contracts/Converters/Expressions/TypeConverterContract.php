<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters\Expressions;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

/**
 * @method TypeContract convert(Expr $expr, ResolverContract $resolver)
 */
interface TypeConverterContract
{
    //
}
