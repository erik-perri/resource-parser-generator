<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem\Contracts;

interface ClassFileLocatorContract
{
    /**
     * @param class-string $className
     * @return string
     */
    public function get(string $className): string;

    /**
     * @param class-string $className
     * @return bool
     */
    public function exists(string $className): bool;
}
