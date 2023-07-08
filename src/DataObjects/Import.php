<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\ImportContract;

class Import implements ImportContract
{
    public function __construct(
        private readonly string $name,
        private readonly string $file,
        private readonly bool $isDefaultExport = false,
    ) {
        //
    }

    public function name(): string
    {
        return $this->name;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function isDefaultExport(): bool
    {
        return $this->isDefaultExport;
    }
}
