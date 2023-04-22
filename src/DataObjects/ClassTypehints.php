<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ClassTypehints
{
    /**
     * @param string $className
     * @param array<string, string[]> $properties
     * @param array<string, string[]> $methods
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties = [],
        public readonly array $methods = [],
    ) {
        //
    }

    /**
     * @param string $methodName
     * @param string[] $returnTypes
     * @return ClassTypehints
     */
    public function addMethod(string $methodName, array $returnTypes): self
    {
        return new self(
            $this->className,
            $this->properties,
            array_merge($this->methods, [$methodName => $returnTypes]),
        );
    }

    /**
     * @param string $propertyName
     * @param string[] $types
     * @return ClassTypehints
     */
    public function addProperty(string $propertyName, array $types): self
    {
        return new self(
            $this->className,
            array_merge($this->properties, [$propertyName => $types]),
            $this->methods,
        );
    }

    /**
     * @param string $methodName
     * @return string[]|null
     */
    public function methodTypes(string $methodName): array|null
    {
        return $this->methods[$methodName] ?? null;
    }

    /**
     * @param string $propertyName
     * @return string[]|null
     */
    public function propertyTypes(string $propertyName): array|null
    {
        return $this->properties[$propertyName] ?? null;
    }
}
