<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Composer\Autoload\ClassLoader;
use RuntimeException;

class ClassFileFinder
{
    private readonly ClassLoader $loader;

    public function __construct(string $vendorPath)
    {
        $this->loader = require $vendorPath . '/autoload.php';
    }

    public function find(string $className): string
    {
        $file = $this->loader->findFile($className);
        if (!$file) {
            throw new RuntimeException('Could not find file for class "' . $className . '"');
        }

        return $file;
    }

    public function has(string $className): bool
    {
        return $this->loader->findFile($className) !== false;
    }
}
