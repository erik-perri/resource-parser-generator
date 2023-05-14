<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodBooleanType;

class BoolType implements TypeContract
{
    public function describe(): string
    {
        return 'bool';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodBooleanType();
    }
}
