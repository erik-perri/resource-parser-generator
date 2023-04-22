<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Constraints;

interface ConstraintContract
{
    public function constraint(): string;

    public function imports(): array;
}
