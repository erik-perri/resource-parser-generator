<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class ArrayType implements TypeContract
{
    public function __construct(
        private readonly TypeContract|null $contains,
    ) {
        //
    }

    public function name(): string
    {
        if ($this->contains) {
            return $this->contains->name() . '[]';
        }

        return 'array';
    }
}
