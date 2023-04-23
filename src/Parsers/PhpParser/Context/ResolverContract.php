<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;

interface ResolverContract
{
    public function resolveClass(Name $name): string;

    /**
     * @param string $variable
     * @return string[]
     */
    public function resolveVariable(string $variable): array;
}
