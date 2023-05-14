<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

interface ParserTypeContract
{
    /**
     * @return string[]
     */
    public function imports(): array;

    public function constraint(): string;
}
