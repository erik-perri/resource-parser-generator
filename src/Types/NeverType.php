<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodNeverType;

class NeverType implements TypeContract
{
    public function __construct()
    {
        //
    }

    public function describe(): string
    {
        return 'never';
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodNeverType();
    }
}
