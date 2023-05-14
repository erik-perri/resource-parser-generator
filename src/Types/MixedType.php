<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodUnknownType;

class MixedType implements TypeContract
{
    public function describe(): string
    {
        return 'mixed';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodUnknownType();
    }
}
