<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class NullType implements TypeContract
{
    public function name(): string
    {
        return 'null';
    }
}
