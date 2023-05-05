<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Types\Contracts\TypeContract;

class UntypedType implements TypeContract
{
    public function describe(): string
    {
        return 'untyped';
    }
}
