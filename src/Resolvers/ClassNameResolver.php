<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;

class ClassNameResolver implements ClassNameResolverContract
{
    public function __construct(
        private readonly FileScope $fileScope,
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
             * @var class-string $className
             */
            $className = $name;

            return $className;
        }

        if (str_contains($name, '\\')) {
            [$firstPart, $remainingParts] = explode('\\', $name, 2);

            foreach ($this->fileScope->imports() as $importAlias => $importPath) {
                if ($importAlias === $firstPart) {
                    /**
                     * @var class-string $className
                     */
                    $className = $importPath . '\\' . $remainingParts;

                    return $className;
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

            return $className;
        }

        return null;
    }
}
