<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ImportGroupContract
{
    public function defaultImport(): ?string;

    /**
     * @return string[]
     */
    public function imports(): array;

    public function module(): string;
}
