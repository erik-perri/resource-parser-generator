<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodNeverType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'never()';
    }

    public function imports(): array
    {
        return ['zod' => ['never']];
    }
}
