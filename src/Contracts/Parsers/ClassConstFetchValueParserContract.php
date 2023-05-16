<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use PhpParser\Node\Expr\ClassConstFetch;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

interface ClassConstFetchValueParserContract
{
    public function parse(ClassConstFetch $value, ResolverContract $resolver): mixed;
}
