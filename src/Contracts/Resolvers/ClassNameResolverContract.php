<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Resolvers;

interface ClassNameResolverContract
{
    /**
     * @param string $name
     * @return class-string|null
     */
    public function resolve(string $name): string|null;
}
