<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Resolvers\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;

class Resolver implements ResolverContract
{
    /**
     * @param ClassNameResolverContract $classResolver
     * @param class-string|null $thisType
     */
    public function __construct(
        private readonly ClassNameResolverContract $classResolver,
        private readonly string|null $thisType,
    ) {
        //
    }

    public static function create(ClassNameResolverContract $classResolver, string|null $thisType): self
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
