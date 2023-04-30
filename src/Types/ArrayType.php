<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class ArrayType implements TypeContract
{
    public function __construct(
        private readonly TypeContract|null $keys,
        private readonly TypeContract|null $values,
    ) {
        //
    }

    public function name(): string
    {
        if ($this->keys && $this->values) {
            return sprintf('array<%s, %s>', $this->keys->name(), $this->values->name());
        }

        if ($this->values instanceof UnionType) {
            return sprintf('array<%s>', $this->values->name());
        }

        if ($this->values) {
            return $this->values->name() . '[]';
        }

        return 'array';
    }
}
