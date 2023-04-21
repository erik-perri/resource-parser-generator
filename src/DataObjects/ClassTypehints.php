<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ClassTypehints
{
    public function __construct(
        public readonly array $properties = [],
        public readonly array $methods = [],
    ) {
        //
    }

    public function addMethod(string $methodName, array $returnTypes): ClassTypehints
    {
        return new self(
            $this->properties,
            array_merge($this->methods, [$methodName => $returnTypes]),
        );
    }

    public function addProperty(string $propertyName, array $types): ClassTypehints
    {
        return new self(
            array_merge($this->properties, [$propertyName => $types]),
            $this->methods,
        );
    }

    public function getMethodTypes(string $methodName): array|null
    {
        return $this->methods[$methodName] ?? null;
    }

    public function getPropertyTypes(string $propertyName): array|null
    {
        return $this->properties[$propertyName] ?? null;
    }
}
