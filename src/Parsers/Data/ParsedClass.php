<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

class ParsedClass
{
    public function __construct(
        public readonly FileScope $fileScope,
        public readonly ClassScope $classScope,
    ) {
        //
    }
}
