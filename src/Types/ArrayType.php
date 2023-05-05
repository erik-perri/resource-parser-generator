<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Types\Contracts\TypeContract;

class ArrayType implements TypeContract
{
    public function __construct(
        private readonly TypeContract|null $keys,
        private readonly TypeContract|null $values,
    ) {
        //
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
