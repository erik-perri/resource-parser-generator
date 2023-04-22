<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Constraints;

class NumberConstraint implements ConstraintContract
{
    public function constraint(): string
    {
        return 'number()';
    }

    public function imports(): array
    {
        return ['number'];
    }
}
