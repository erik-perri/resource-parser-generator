<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ResourceParserGenerator\DataObjects\ResourcePath;

class ResourceFileLocator
{
    /**
     * @param ResourcePath $path
     * @return string[]
     */
    public function files(ResourcePath $path): array
    {
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path->path)),
            $path->fileMatch,
            RegexIterator::GET_MATCH
        );

        return array_keys(iterator_to_array($iterator));
    }
}
