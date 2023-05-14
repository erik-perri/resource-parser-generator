<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodUndefinedType implements ParserTypeContract
{
    public function imports(): array
    {
        return ['undefined'];
    }

    public function constraint(): string
    {
        return 'undefined()';
    }
}
