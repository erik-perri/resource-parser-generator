<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use Exception;
use ResourceParserGenerator\Builders\Constraints\CompoundConstraint;
use ResourceParserGenerator\Builders\Constraints\ConstraintContract;
use ResourceParserGenerator\Builders\Constraints\IntegerConstraint;
use ResourceParserGenerator\Builders\Constraints\NullConstraint;
use ResourceParserGenerator\Builders\Constraints\StringConstraint;

class ParserConstraintBuilder
{
    public function create(array $types): ConstraintContract
    {
        if (count($types) > 1) {
            return new CompoundConstraint(
                ...array_map(fn($type) => $this->create([$type]), $types),
            );
        }

        $type = $types[0];

        return match ($type) {
            'string' => new StringConstraint(),
            'int' => new IntegerConstraint(),
            'null' => new NullConstraint(),
            default => throw new Exception("Unknown type: $type"),
        };
    }
}
