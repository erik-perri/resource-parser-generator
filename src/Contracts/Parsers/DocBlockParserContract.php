<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Parsers\Data\DocBlock;

interface DocBlockParserContract
{
    public function parse(string $content, ResolverContract $resolver): DocBlock;
}
