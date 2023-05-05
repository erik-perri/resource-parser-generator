<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers\Contracts;

use ResourceParserGenerator\Types\Contracts\TypeContract;

interface VariableResolverContract
{
    public function resolve(string $name): TypeContract|null;
}
