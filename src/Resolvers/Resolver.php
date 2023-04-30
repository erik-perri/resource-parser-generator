<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Contracts\ResolverContract;

class Resolver implements ResolverContract
{
    /**
     * @param ClassNameResolver $classResolver
     * @param class-string|null $thisType
     */
    public function __construct(
        private readonly ClassNameResolver $classResolver,
        private readonly string|null $thisType,
    ) {
        //
    }

    public static function create(ClassNameResolver $classResolver, string|null $thisType): self
    {
        return resolve(self::class, [
            'classResolver' => $classResolver,
            'thisType' => $thisType,
        ]);
    }

    public function resolveClass(string $name): string|null
    {
        return $this->classResolver->resolve($name);
    }

    public function resolveThis(): string|null
    {
        return $this->thisType;
    }
}
