<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Resolvers;

use ResourceParserGenerator\Contracts\Types\TypeContract;

interface VariableResolverContract
{
    public function resolve(string $name): TypeContract|null;
}
