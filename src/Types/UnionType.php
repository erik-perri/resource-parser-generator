<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodNullableType;
use ResourceParserGenerator\Types\Zod\ZodOptionalType;
use ResourceParserGenerator\Types\Zod\ZodUnionType;
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
            ...$this->types->all(),
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
     * @param class-string $typeClass
     * @return bool
     */
    public function hasType(string $typeClass): bool
    {
        return $this->types->some(fn(TypeContract $type) => $type instanceof $typeClass);
    }

    /**
     * @param callable(TypeContract): bool $callback
     * @return self
     */
    public function removeFromUnion(callable $callback): self
    {
        return new self(
            ...$this->types->reject($callback)->all(),
        );
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
            return $newTypes->firstOrFail();
        }

        return new self(...$newTypes->all());
    }

    public function parserType(): ParserTypeContract
    {
        /**
         * TODO Flatten the union before this point
         * @var Collection<int, TypeContract> $flatTypes
         */
        $flatTypes = $this->types->map(function (TypeContract $type) {
            return $type instanceof UnionType ? $type->types() : $type;
        })->flatten();

        if ($flatTypes->count() === 2) {
            if ($this->hasType(NullType::class)) {
                return new ZodNullableType($flatTypes
                    ->filter(fn(TypeContract $type) => !($type instanceof NullType))
                    ->firstOrFail()
                    ->parserType());
            }

            if ($this->hasType(UndefinedType::class)) {
                return new ZodOptionalType($flatTypes
                    ->filter(fn(TypeContract $type) => !($type instanceof UndefinedType))
                    ->firstOrFail()
                    ->parserType());
            }
        }

        return new ZodUnionType(...$flatTypes->map(fn(TypeContract $type) => $type->parserType())->all());
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function types(): Collection
    {
        return $this->types->collect();
    }
}
