<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use RuntimeException;

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

    /**
     * @param callable(TypeContract): bool $callback
     * @return self
     */
    public function removeFromUnion(callable $callback): self
    {
        return new self(
            ...$this->types->reject($callback)->values(),
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

    public function removeNullable(): TypeContract
    {
        $newTypes = $this->types->reject(fn(TypeContract $type) => $type instanceof NullType);
        $newLength = $newTypes->count();

        if ($newLength === $this->types->count()) {
            throw new RuntimeException('Cannot remove nullable from non-nullable union');
        }

        if (!$newLength) {
            throw new RuntimeException('Removing nullable would produce empty union');
        }

        if ($newLength === 1) {
            return $newTypes->first();
        }

        return new self(...$newTypes->values());
    }
}
