<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\TypeContract;

class UnionType implements TypeContract
{
    /**
     * @var Collection<int, TypeContract>
     */
    private readonly Collection $types;

    public function __construct(
        TypeContract ...$type,
    ) {
        /**
         * @var Collection<int, TypeContract> $types
         */
        $types = collect($type);

        $this->types = $types;
    }

    public function name(): string
    {
        return $this->types
            ->map(fn(TypeContract $type) => $type->name())
            ->unique()
            ->implode('|');
    }
}
