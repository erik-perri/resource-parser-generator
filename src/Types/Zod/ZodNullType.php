<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodNullType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'null()';
    }

    public function imports(): array
    {
        return ['zod' => ['null']];
    }
}
