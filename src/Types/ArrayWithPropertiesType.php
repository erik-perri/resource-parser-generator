<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;

class ArrayWithPropertiesType implements TypeContract, TypeWithChildrenContract
{
    /**
     * @param Collection<string, TypeContract> $properties
     */
    public function __construct(
        private readonly Collection $properties,
    ) {
        //
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function children(): Collection
    {
        /**
         * @var Collection<int, TypeContract>
         */
        return $this->properties
            ->collect()
            ->map(fn(TypeContract $type) => $type instanceof TypeWithChildrenContract
                ? $type->children()
                : [$type])
            ->flatten();
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
    public function describeArray(): array
    {
        // @phpstan-ignore-next-line -- The type complaint is due to the potentially recursive nature of this method.
        return $this->properties()->mapWithKeys(fn(TypeContract $type, string $property) => [
            $property => $type instanceof self
                ? $type->describeArray()
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
