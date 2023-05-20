<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Resolvers;

interface ResourceResolverContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return string
     */
    public function resolveFileName(string $className, string $methodName): string;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return string
     */
    public function resolveVariableName(string $className, string $methodName): string;
}
