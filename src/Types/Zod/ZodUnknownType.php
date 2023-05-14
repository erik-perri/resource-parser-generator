<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodUnknownType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'unknown()';
    }

    public function imports(): array
    {
        return ['zod' => ['unknown']];
    }
}
