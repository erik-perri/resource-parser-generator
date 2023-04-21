<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Composer\Autoload\ClassLoader;

class ClassFileFinder
{
    private readonly ClassLoader $loader;

    public function __construct(string $vendorPath)
    {
        $this->loader = require $vendorPath . '/autoload.php';
    }

    public function find(string $className): string
    {
        return $this->loader->findFile($className);
    }
}
