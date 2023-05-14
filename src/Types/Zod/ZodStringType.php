<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodStringType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'string()';
    }

    public function imports(): array
    {
        return ['zod' => ['string']];
    }
}
