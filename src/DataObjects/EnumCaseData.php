<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class EnumCaseData
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        public readonly ?string $comment,
    ) {
        //
    }
}
