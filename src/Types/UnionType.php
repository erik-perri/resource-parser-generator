<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class UnionType implements TypeContract
{
    /**
     * @var Collection<int, TypeContract>
     */
    private readonly Collection $types;

    public function __construct(
        TypeContract ...$type,
    ) {
        /**
         * @var Collection<int, TypeContract> $types
         */
        $types = collect($type);

        $this->types = $types;
    }

    public function addToUnion(TypeContract $type): self
    {
        return new self(
            $type,
            ...$this->types->values(),
        );
    }

    public function describe(): string
    {
        return $this->types
            ->map(fn(TypeContract $type) => $type->describe())
            ->sort(fn(string $a, string $b) => strnatcasecmp($a, $b))
            ->unique()
            ->implode('|');
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function types(): Collection
    {
        return $this->types->collect();
    }
}
