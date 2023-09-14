<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes\Collections;

use InvalidArgumentException;
use ResourceParserGenerator\TypeScript\AST\Nodes\Node;

class ModifierNodeCollection extends NodeCollection
{
    protected function typeCheck(mixed $value): void
    {
        parent::typeCheck($value);

        /** @var Node $value */
        if (!$value->kind()->isModifier()) {
            throw new InvalidArgumentException(
                sprintf('The value must be an instance of %s with a modifier kind.', Node::class),
            );
        }
    }
}
