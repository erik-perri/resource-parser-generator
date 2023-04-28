<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class ObjectType implements TypeContract
{
    public function name(): string
    {
        return 'object';
    }
}
