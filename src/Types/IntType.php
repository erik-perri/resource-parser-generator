<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodNumberType;

class IntType implements TypeContract
{
    public function describe(): string
    {
        return 'int';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodNumberType();
    }
}
