<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodStringType;

class StringType implements TypeContract
{
    public function describe(): string
    {
        return 'string';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodStringType();
    }
}
