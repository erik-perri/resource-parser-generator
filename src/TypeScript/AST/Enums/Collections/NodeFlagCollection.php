<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Enums\Collections;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use ResourceParserGenerator\TypeScript\AST\Enums\NodeFlag;

/**
 * @extends Collection<int, NodeFlag>
 */
class NodeFlagCollection extends Collection
{
    public function offsetGet($key): ?NodeFlag
    {
        return parent::offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->typeCheck($value);

        parent::offsetSet($key, $value);
    }

    public function toInt(): int
    {
        $int = NodeFlag::None->value;

        foreach ($this as $flag) {
            $int &= $flag->value;
        }

        return $int;
    }

    protected function typeCheck(mixed $value): void
    {
        if (!$value instanceof NodeFlag) {
            throw new InvalidArgumentException(sprintf('The value must be an instance of %s.', NodeFlag::class));
        }
    }
}
