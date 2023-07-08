<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\Resolvers\ClassNameResolverContract;
use ResourceParserGenerator\Parsers\Data\FileScope;

class ClassNameResolver implements ClassNameResolverContract
{
    public function __construct(
        private readonly FileScope $fileScope,
        private readonly ClassFileLocatorContract $classFileLocator,
    ) {
        //
    }

    public static function create(FileScope $fileScope): self
    {
        return resolve(self::class, [
            'fileScope' => $fileScope,
        ]);
    }

    public function resolve(string $name): string|null
    {
        if (str_starts_with($name, '\\')) {
            /**
             * @var class-string
             */
            return $name;
        }

        if (str_contains($name, '\\')) {
            [$firstPart, $remainingParts] = explode('\\', $name, 2);

            foreach ($this->fileScope->imports() as $importAlias => $importPath) {
                if ($importAlias === $firstPart) {
                    /**
                     * @var class-string $className
                     */
                    $className = $importPath . '\\' . $remainingParts;

                    if ($this->classFileLocator->exists($className) || class_exists($className)) {
                        return $className;
                    }
                }
            }
        }

        foreach ($this->fileScope->imports() as $importAlias => $importPath) {
            if ($importPath === $name || $importAlias === $name) {
                return $importPath;
            }
        }

        if ($this->fileScope->namespace()) {
            /**
             * @var class-string $className
             */
            $className = $this->fileScope->namespace() . '\\' . $name;

            if ($this->classFileLocator->exists($className) || class_exists($className)) {
                return $className;
            }
        }

        if ($this->fileScope->hasClass($name)) {
            /**
             * @var class-string $name
             */
            if ($this->classFileLocator->exists($name) || class_exists($name)) {
                return $name;
            }
        }

        return null;
    }
}
