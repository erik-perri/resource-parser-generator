<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodUndefinedType;

class UndefinedType implements TypeContract
{
    public function describe(): string
    {
        return 'undefined';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodUndefinedType();
    }
}
