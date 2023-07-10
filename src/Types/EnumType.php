<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;

class EnumType implements TypeContract, TypeWithChildrenContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param TypeContract $backingType
     */
    public function __construct(
        public readonly string $fullyQualifiedName,
        public readonly TypeContract $backingType
    ) {
        //
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function children(): Collection
    {
        return collect([$this->backingType]);
    }

    public function describe(): string
    {
        return sprintf('enum<%s, %s>', $this->fullyQualifiedName, $this->backingType->describe());
    }
}
