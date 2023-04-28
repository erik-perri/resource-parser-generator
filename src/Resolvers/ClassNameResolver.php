<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use InvalidArgumentException;
use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;

class ClassNameResolver implements ClassNameResolverContract
{
    public function __construct(
        private readonly FileScope $fileScope,
    ) {
        //
    }

    public static function make(FileScope $fileScope): self
    {
        return resolve(self::class, [
            'fileScope' => $fileScope,
        ]);
    }

    public function resolve(string $name, bool $isRelative): string|null
    {
        if (!$isRelative) {
            foreach ($this->fileScope->imports() as $alias => $class) {
                if ($class === $name || $alias === $name) {
                    return $class;
                }
            }
        } else {
            if (!str_contains($name, '\\')) {
                throw new InvalidArgumentException(
                    sprintf('Relative class name "%s" must contain a namespace separator', $name),
                );
            }

            [$firstPart, $remainingParts] = explode('\\', $name, 2);
            foreach ($this->fileScope->imports() as $alias => $class) {
                if ($alias === $firstPart) {
                    /**
                     * @var class-string $combinedClass
                     */
                    $combinedClass = $class . '\\' . $remainingParts;

                    return $combinedClass;
                }
            }
        }

        return null;
    }
}
