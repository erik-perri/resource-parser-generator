<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class ResourceTypeProcessor
{
    /**
     * @param TypeContract $type
     * @param callable(TypeContract): ?TypeContract $callback
     * @return TypeContract
     */
    public function process(TypeContract $type, callable $callback): TypeContract
    {
        if ($type instanceof Types\UnionType) {
            return new Types\UnionType(
                ...$type->types()->map(fn(TypeContract $type) => $this->process($type, $callback))->all(),
            );
        }

        if ($type instanceof Types\ArrayWithPropertiesType) {
            return new Types\ArrayWithPropertiesType(
                $type->properties()->map(fn(TypeContract $type) => $this->process($type, $callback)),
            );
        }

        if ($type instanceof Types\ArrayType) {
            return new Types\ArrayType(
                $type->keys ? $this->process($type->keys, $callback) : null,
                $type->values ? $this->process($type->values, $callback) : null,
            );
        }

        return $callback($type) ?? $type;
    }
}
