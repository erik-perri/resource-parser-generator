<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\TypeContract;

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

    public function name(): string
    {
        return 'array[...]';
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }
}
