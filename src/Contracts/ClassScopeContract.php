<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ClassScopeContract
{
    public function constant(string $name): ClassConstantContract|null;

    /**
     * @param class-string $className
     * @return bool
     */
    public function hasParent(string $className): bool;

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string;

    /**
     * @return Collection<string, ClassMethodScopeContract>
     */
    public function methods(): Collection;

    public function method(string $name): ClassMethodScopeContract|null;

    public function name(): string;

    public function parent(): ClassScopeContract|null;

    public function property(string $name): ClassPropertyContract|null;

    public function propertyType(string $name): TypeContract|null;

    public function resolver(): ResolverContract;
}
