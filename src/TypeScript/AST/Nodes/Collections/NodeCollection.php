<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes\Collections;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use ResourceParserGenerator\TypeScript\AST\Nodes\Node;

/**
 * @extends Collection<int, Node>
 */
class NodeCollection extends Collection
{
    public function offsetGet($key): ?Node
    {
        return parent::offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->typeCheck($value);

        parent::offsetSet($key, $value);
    }

    protected function typeCheck(mixed $value): void
    {
        if (!$value instanceof Node) {
            throw new InvalidArgumentException(sprintf('The value must be an instance of %s.', Node::class));
        }
    }
}
