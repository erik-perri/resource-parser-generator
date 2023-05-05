<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class StringType implements TypeContract
{
    public function describe(): string
    {
        return 'string';
    }
}
