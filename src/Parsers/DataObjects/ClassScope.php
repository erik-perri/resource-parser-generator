<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use RuntimeException;

class ClassScope
{
    /**
     * @var Collection<int, ClassProperty>
     */
    private readonly Collection $properties;

    public function __construct(
        public readonly FileScope $file,
        public readonly string $name,
        public readonly ClassScope|null $extends,
    ) {
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

    public function addProperty(ClassProperty $classProperty): self
    {
        $this->properties->push($classProperty);

        return $this;
    }

    /**
     * @return Collection<int, ClassProperty>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }

    public function property(string $name): ClassProperty
    {
        $property = $this->properties->first(fn(ClassProperty $property) => $property->name === $name);

        if ($property === null && $this->extends) {
            $property = $this->extends->property($name);
        }

        if ($property === null) {
            throw new RuntimeException(sprintf('Property "%s" not found', $name));
        }

        return $property;
    }
}
