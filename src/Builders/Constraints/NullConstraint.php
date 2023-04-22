<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Constraints;

class NullConstraint implements ConstraintContract
{
    public function constraint(): string
    {
        return 'null()';
    }

    public function imports(): array
    {
        return ['null'];
    }
}
