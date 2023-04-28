<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Composer\Autoload\ClassLoader;
use InvalidArgumentException;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use RuntimeException;

class ClassFileLocator implements ClassFileLocatorContract
{
    private readonly ClassLoader $loader;

    public function __construct(string $vendorPath)
    {
        if (!file_exists($vendorPath . '/autoload.php')) {
            throw new InvalidArgumentException(sprintf('Could not find composer autoload file at "%s"', $vendorPath));
        }

        $loader = require $vendorPath . '/autoload.php';
        if (!($loader instanceof ClassLoader)) {
            throw new InvalidArgumentException(sprintf('Unexpected composer autoload type at "%s"', $vendorPath));
        }

        $this->loader = $loader;
    }

    public function get(string $className): string
    {
        $file = $this->loader->findFile($className);
        if (!$file) {
            throw new RuntimeException(sprintf('Could not find file for class "%s"', $className));
        }

        return $file;
    }

    public function exists(string $className): bool
    {
        return $this->loader->findFile($className) !== false;
    }
}
