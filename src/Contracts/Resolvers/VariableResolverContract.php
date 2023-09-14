<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Resolvers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface VariableResolverContract
{
    /**
     * @param Collection<string, TypeContract> $variables
     * @return self
     */
    public function extend(Collection $variables): self;

    public function resolve(string $name): TypeContract|null;
}
