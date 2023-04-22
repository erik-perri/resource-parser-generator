<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

interface ResolverContract
{
    public function resolveClass(string $class): string;

    /**
     * @param string $variable
     * @return string[]
     */
    public function resolveVariable(string $variable): array;
}
