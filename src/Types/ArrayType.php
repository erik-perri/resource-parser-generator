<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;

class ArrayType implements TypeContract, TypeWithChildrenContract
{
    public function __construct(
        public readonly TypeContract|null $keys,
        public readonly TypeContract|null $values,
    ) {
        //
    }

    /**
     * @return Collection<int, TypeContract>
     */
    public function children(): Collection
    {
        return collect([$this->keys, $this->values])
            ->filter();
    }

    public function describe(): string
    {
        if ($this->keys && $this->values) {
            return sprintf('array<%s, %s>', $this->keys->describe(), $this->values->describe());
        }

        if ($this->values instanceof UnionType) {
            return sprintf('array<%s>', $this->values->describe());
        }

        if ($this->values) {
            return $this->values->describe() . '[]';
        }

        return 'array';
    }
}
