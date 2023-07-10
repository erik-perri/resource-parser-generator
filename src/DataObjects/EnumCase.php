<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class EnumCase
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly ?string $comment,
    ) {
        //
    }
}
