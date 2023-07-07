<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class ObjectType implements TypeContract
{
    public function describe(): string
    {
        return 'object';
    }
}
