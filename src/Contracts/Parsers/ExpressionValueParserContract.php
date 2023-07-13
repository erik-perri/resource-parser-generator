<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use PhpParser\Node\Expr;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

interface ExpressionValueParserContract
{
    public function parse(Expr $expr, ResolverContract $resolver): mixed;
}
