<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers\Contracts;

use ResourceParserGenerator\Types\Contracts\TypeContract;

interface ResolverContract
{
    /**
     * @param string $name
     * @return class-string|null
     */
    public function resolveClass(string $name): string|null;

    /**
     * @return class-string|null
     */
    public function resolveThis(): string|null;

    /**
     * @param string $name
     * @return TypeContract|null
     */
    public function resolveVariable(string $name): TypeContract|null;
}
