<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ClassNameResolverContract
{
    /**
     * @param string $name
     * @param bool $isRelative
     * @return class-string|null
     */
    public function resolve(string $name, bool $isRelative): string|null;
}
