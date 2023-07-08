<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ImportContract;
use ResourceParserGenerator\Contracts\ImportGroupContract;
use RuntimeException;

class ImportCollection implements ImportCollectionContract
{
    /**
     * @var Collection<int, ImportContract>
     */
    private readonly Collection $imports;

    public function __construct(ImportContract ...$imports)
    {
        $this->imports = collect(array_values($imports));
    }

    public function concat(ImportContract $import): self
    {
        if ($import->isDefaultExport()) {
            $existingDefault = $this->imports->first(
                fn(ImportContract $existingImport) => $existingImport->isDefaultExport()
                    && $existingImport->file() === $import->file()
            );
            if ($existingDefault) {
                throw new RuntimeException(sprintf(
                    'Cannot add default import "%s" from file "%s" because it already has a default import.',
                    $import->name(),
                    $import->file(),
                ));
            }
        } else {
            $existing = $this->imports->first(
                fn(ImportContract $existingImport) => $existingImport->name() === $import->name()
                    && $existingImport->file() === $import->file()
            );
            if ($existing) {
                return new self(...$this->imports->all());
            }
        }

        return new self($import, ...$this->imports->all());
    }

    /**
     * @return Collection<int, ImportContract>
     */
    public function imports(): Collection
    {
        return $this->imports->collect();
    }

    public function merge(ImportContract|ImportCollectionContract $imports): self
    {
        $merged = new self(...$this->imports->all());

        if ($imports instanceof ImportContract) {
            return $merged->concat($imports);
        }

        return $imports->imports()->reduce(
            fn(ImportCollection $merged, ImportContract $import) => $merged->concat($import),
            $merged,
        );
    }

    /**
     * @return Collection<int, ImportGroupContract>
     */
    public function groupForView(): Collection
    {
        $importsByFile = $this->imports->groupBy(fn(ImportContract $import) => $this->stripExtension($import->file()))
            ->sortKeys();

        /**
         * @var Collection<int, ImportGroupContract>
         */
        $importGroups = $importsByFile->map(
            fn(Collection $imports, string $file) => new ImportGroup($file, new self(...$imports->all())),
        );

        return $importGroups->values();
    }

    private function stripExtension(string $filePath): string
    {
        $info = pathinfo($filePath);

        return isset($info['extension'])
            ? Str::of($filePath)->beforeLast('.' . $info['extension'])->value()
            : $filePath;
    }
}
