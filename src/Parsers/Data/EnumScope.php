<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use RuntimeException;

class EnumScope implements ClassScopeContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param TypeContract $type
     */
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly TypeContract $type,
    ) {
        //
    }

    public static function create(string $fullyQualifiedName, TypeContract $type): self
    {
        return resolve(self::class, [
            'fullyQualifiedName' => $fullyQualifiedName,
            'type' => $type,
        ]);
    }

    public function fullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    public function constant(string $name): ClassConstantContract|null
    {
        throw new RuntimeException('Enum constants are not supported');
    }

    public function hasParent(string $className): bool
    {
        return false;
    }

    public function methods(): Collection
    {
        throw new RuntimeException('Enum methods are not supported');
    }

    public function method(string $name): ClassMethodScopeContract|null
    {
        throw new RuntimeException('Enum methods are not supported');
    }

    public function name(): string
    {
        return class_basename($this->fullyQualifiedName);
    }

    public function parent(): ClassScopeContract|null
    {
        return null;
    }

    public function property(string $name): ClassPropertyContract|null
    {
        if ($name === 'value') {
            return VirtualClassProperty::create($this->type);
        }

        throw new RuntimeException('Enum properties are not supported');
    }

    public function propertyType(string $name): TypeContract|null
    {
        if ($name === 'value') {
            return $this->type;
        }

        throw new RuntimeException(sprintf('Enum property "%s" is not supported', $name));
    }

    public function resolver(): ResolverContract
    {
        throw new RuntimeException('Enum resolution is not supported');
    }
}
