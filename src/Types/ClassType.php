<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class ClassType implements TypeContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param string|null $alias
     * @param Collection<int, TypeContract>|null $generics
     */
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly string|null $alias,
        private readonly Collection|null $generics = null,
    ) {
        //
    }

    public function alias(): string|null
    {
        return $this->alias;
    }

    /**
     * @return class-string|string
     */
    public function describe(): string
    {
        if ($this->generics) {
            return sprintf(
                '%s<%s>',
                $this->fullyQualifiedName,
                $this->generics->map(fn(TypeContract $type) => $type->describe())->join(', '),
            );
        }

        return $this->fullyQualifiedName;
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    /**
     * @return Collection<int, TypeContract>|null
     */
    public function generics(): Collection|null
    {
        return $this->generics?->collect();
    }
}
