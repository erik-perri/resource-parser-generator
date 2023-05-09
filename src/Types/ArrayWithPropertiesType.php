<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ArrayWithPropertiesType implements TypeContract
{
    /**
     * @param Collection<string, TypeContract> $properties
     */
    public function __construct(
        private readonly Collection $properties,
    ) {
        //
    }

    public function describe(): string
    {
        return sprintf(
            'array<{%s}>',
            $this->properties->map(
                fn(TypeContract $type, string $property) => sprintf('%s: %s', $property, $type->describe()),
            )->join('; '),
        );
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function describeRecursive(): array
    {
        // @phpstan-ignore-next-line -- The type complaint is due to the potentially recursive nature of this method.
        return $this->properties()->mapWithKeys(fn(TypeContract $type, string $property) => [
            $property => $type instanceof self
                ? $type->describeRecursive()
                : $type->describe(),
        ])->all();
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }
}
