<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class BoolType implements TypeContract
{
    public function describe(): string
    {
        return 'bool';
    }
}
