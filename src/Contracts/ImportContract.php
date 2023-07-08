<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ImportContract
{
    public function name(): string;

    public function file(): string;

    public function isDefaultExport(): bool;
}
