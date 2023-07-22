<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Collection;

class ImportPathFinder
{
    public function find(string $importingFrom, string $importingTo): ?string
    {
        $fromParts = $this->splitPath($importingFrom);
        $toParts = $this->splitPath($importingTo);
        $sameParts = collect($fromParts)
            ->zip($toParts)
            ->takeUntil(fn(Collection $pair) => $pair[0] !== $pair[1])
            ->count();

        if ($sameParts === 0) {
            return null;
        }

        $pathDifference = count($toParts) - $sameParts;
        $pathPrefix = $pathDifference === 0
            ? './'
            : str_repeat('../', $pathDifference);

        $importParts = array_slice($fromParts, $sameParts);

        if (count($importParts) === 0) {
            return rtrim($pathPrefix, '/');
        }

        return $pathPrefix . implode('/', $importParts);
    }

    /**
     * @param string $importingFrom
     * @return string[]
     */
    private function splitPath(string $importingFrom): array
    {
        return explode('/', str_replace('\\', '/', $importingFrom));
    }
}
