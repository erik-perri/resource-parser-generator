<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodStringType implements ParserTypeContract
{
    public function imports(): array
    {
        return ['string'];
    }

    public function constraint(): string
    {
        return 'string()';
    }
}
