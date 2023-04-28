<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ClassNameResolverContract
{
    /**
     * @param string $name
     * @return class-string|null
     */
    public function resolve(string $name): string|null;
}
