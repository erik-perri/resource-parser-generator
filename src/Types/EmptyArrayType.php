<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class EmptyArrayType implements TypeContract
{
    public function describe(): string
    {
        return '[]';
    }
}
