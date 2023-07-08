<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class IntersectionType implements TypeContract
{
    /**
     * @var Collection<int, TypeContract>
     */
    private readonly Collection $types;

    public function __construct(TypeContract ...$type)
    {
        $this->types = collect(array_values($type));
    }

    public function describe(): string
    {
        return $this->types
            ->map(fn(TypeContract $type) => $type->describe())
            ->unique()
            ->implode('&');
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function types(): Collection
    {
        return $this->types->collect();
    }
}
