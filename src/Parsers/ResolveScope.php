<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Parsers\PhpParser\UseStatementParser;

class ResolveScope
{
    private array $imports = [];

    public function __construct(private readonly UseStatementParser $useStatementParser)
    {
        //
    }

    public function loadImports(string $file): self
    {
        $this->imports = $this->useStatementParser->parse($file);

        return $this;
    }

    public function resolveClass(string $className): string
    {
        if (isset($this->imports[$className])) {
            return $this->imports[$className];
        }

        return $className;
    }
}
