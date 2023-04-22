<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use Exception;
use ResourceParserGenerator\Builders\Constraints\ConstraintContract;
use ResourceParserGenerator\Builders\Constraints\NullConstraint;
use ResourceParserGenerator\Builders\Constraints\NumberConstraint;
use ResourceParserGenerator\Builders\Constraints\StringConstraint;
use ResourceParserGenerator\Builders\Constraints\UnionConstraint;

class ParserConstraintBuilder
{
    /**
     * @param string[] $types
     * @throws Exception
     */
    public function create(array $types): ConstraintContract
    {
        if (count($types) > 1) {
            return new UnionConstraint(
                ...array_map(fn($type) => $this->create([$type]), $types),
            );
        }

        $type = $types[0];

        return match ($type) {
            'int' => new NumberConstraint(),
            'null' => new NullConstraint(),
            'string' => new StringConstraint(),
            default => throw new Exception('Unhandled type for constraint "' . $type . '"'),
        };
    }
}
