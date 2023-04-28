<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class VoidType implements TypeContract
{
    public function name(): string
    {
        return 'void';
    }
}
