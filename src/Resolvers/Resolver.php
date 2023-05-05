<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Resolvers\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Resolvers\Contracts\VariableResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class Resolver implements ResolverContract
{
    /**
     * @param ClassNameResolverContract $classResolver
     * @param VariableResolverContract|null $variableResolver
     * @param class-string|null $thisType
     */
    public function __construct(
        private readonly ClassNameResolverContract $classResolver,
        private readonly VariableResolverContract|null $variableResolver,
        private readonly string|null $thisType,
    ) {
        //
    }

    public static function create(
        ClassNameResolverContract $classResolver,
        VariableResolverContract|null $variableResolver,
        string|null $thisType,
    ): self {
        return resolve(self::class, [
            'classResolver' => $classResolver,
            'variableResolver' => $variableResolver,
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

    public function resolveVariable(string $name): TypeContract|null
    {
        return $this->variableResolver?->resolve($name);
    }
}
