<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodNumberType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'number()';
    }

    public function imports(): array
    {
        return ['zod' => ['number']];
    }
}
