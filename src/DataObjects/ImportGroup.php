<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ImportContract;
use ResourceParserGenerator\Contracts\ImportGroupContract;

class ImportGroup implements ImportGroupContract
{
    public function __construct(
        private readonly string $module,
        private readonly ImportCollectionContract $imports,
    ) {
        //
    }

    public function defaultImport(): ?string
    {
        return $this->imports->imports()
            ->first(fn(ImportContract $import) => $import->isDefaultExport())
            ?->name();
    }

    public function imports(): array
    {
        return $this->imports->imports()
            ->filter(fn(ImportContract $import) => !$import->isDefaultExport())
            ->map(fn(ImportContract $imports) => $imports->name())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function module(): string
    {
        return $this->module;
    }
}
