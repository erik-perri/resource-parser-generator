<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;

class ResourcePath implements ParserSourceContract
{
    public function __construct(
        public readonly string $path,
        public readonly string $fileMatch = '/\.php$/i',
    ) {
        //
    }
}
