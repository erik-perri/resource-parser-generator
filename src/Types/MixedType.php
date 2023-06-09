<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class MixedType implements TypeContract
{
    public function describe(): string
    {
        return 'mixed';
    }
}
