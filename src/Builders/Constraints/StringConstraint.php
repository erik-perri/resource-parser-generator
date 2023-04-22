<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Constraints;

class StringConstraint implements ConstraintContract
{
    public function constraint(): string
    {
        return 'string()';
    }

    public function imports(): array
    {
        return ['string'];
    }
}
