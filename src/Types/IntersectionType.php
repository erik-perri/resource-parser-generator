<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Zod\ZodIntersectionType;

class IntersectionType implements TypeContract
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

    public function describe(): string
    {
        return $this->types
            ->map(fn(TypeContract $type) => $type->describe())
            ->unique()
            ->implode('&');
    }

    public function parserType(): ParserTypeContract
    {
        return new ZodIntersectionType(
            ...$this->types->map(fn(TypeContract $type) => $type->parserType())->all(),
        );
    }
}
