<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodNullType;

class NullType implements TypeContract
{
    public function describe(): string
    {
        return 'null';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodNullType();
    }
}
