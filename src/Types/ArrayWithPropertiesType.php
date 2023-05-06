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
     * @return Collection<string, TypeContract>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }
}
