<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodNumberType;

class FloatType implements TypeContract
{
    public function describe(): string
    {
        return 'float';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodNumberType();
    }
}
