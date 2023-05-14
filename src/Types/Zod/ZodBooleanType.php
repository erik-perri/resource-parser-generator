<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodBooleanType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'boolean()';
    }

    public function imports(): array
    {
        return ['zod' => ['boolean']];
    }
}
