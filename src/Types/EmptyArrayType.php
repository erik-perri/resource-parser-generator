<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodArrayType;
use ResourceParserGenerator\Types\Zod\ZodNeverType;

class EmptyArrayType implements TypeContract
{
    public function describe(): string
    {
        return '[]';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodArrayType(null, new ZodNeverType());
    }
}
