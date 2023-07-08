<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Filesystem;

interface ResourceFileFormatLocatorContract
{
    /**
     * @param string $fileName
     * @return array<array{class-string, string}>
     */
    public function formats(string $fileName): array;
}
