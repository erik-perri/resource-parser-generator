<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Types\Contracts\TypeContract;

class UndefinedType implements TypeContract
{
    public function describe(): string
    {
        return 'undefined';
    }
}
