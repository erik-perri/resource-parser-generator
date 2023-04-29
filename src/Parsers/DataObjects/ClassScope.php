<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use RuntimeException;

class ClassScope
{
    /**
     * @var Collection<string, ClassMethod>
     */
    private readonly Collection $methods;

    /**
     * @var Collection<string, ClassProperty>
     */
    private readonly Collection $properties;

    public function __construct(
        public readonly FileScope $file,
        public readonly string $name,
        public readonly ClassScope|null $extends,
    ) {
        $this->methods = collect();
        $this->properties = collect();
    }

    public static function create(
        FileScope $file,
        string $name,
        ClassScope|null $extends,
    ): self {
        return resolve(self::class, [
            'file' => $file,
            'name' => $name,
            'extends' => $extends,
        ]);
    }

    /**
     * @return Collection<string, ClassMethod>
     */
    public function methods(): Collection
    {
        return $this->methods->collect();
    }

    public function method(string $name): ClassMethod
    {
        $method = $this->methods->get($name);

        if ($method === null && $this->extends) {
            $method = $this->extends->method($name);
        }

        if ($method === null) {
            throw new RuntimeException(sprintf('Method "%s" not found', $name));
        }

        return $method;
    }

    /**
     * @return Collection<string, ClassProperty>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }

    public function property(string $name): ClassProperty
    {
        $property = $this->properties->get($name);

        if ($property === null && $this->extends) {
            $property = $this->extends->property($name);
        }

        if ($property === null) {
            throw new RuntimeException(sprintf('Property "%s" not found', $name));
        }

        return $property;
    }

    public function setMethod(ClassMethod $method): self
    {
        if ($this->methods->has($method->name)) {
            throw new RuntimeException(sprintf('Method "%s" already exists on "%s"', $method->name, $this->name));
        }

        $this->methods->put($method->name, $method);

        return $this;
    }

    public function setProperty(ClassProperty $property): self
    {
        if ($this->properties->has($property->name)) {
            throw new RuntimeException(sprintf('Property "%s" already exists on "%s"', $property->name, $this->name));
        }

        $this->properties->put($property->name, $property);

        return $this;
    }
}
