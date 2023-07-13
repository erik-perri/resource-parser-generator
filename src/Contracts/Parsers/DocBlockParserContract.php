<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\DataObjects\DocBlockData;

interface DocBlockParserContract
{
    public function parse(string $content, ResolverContract $resolver): DocBlockData;
}
