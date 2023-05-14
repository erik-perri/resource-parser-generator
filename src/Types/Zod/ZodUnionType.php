<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodUnionType implements ParserTypeContract
{
    /**
     * @var Collection<int|string, ParserTypeContract>
     */
    private readonly Collection $types;

    public function __construct(ParserTypeContract ...$types)
    {
        $this->types = collect($types);
    }

    public function imports(): array
    {
        /**
         * @var Collection<int, string> $imports
         */
        $imports = $this->types->map(fn(ParserTypeContract $type) => $type->imports())
            ->flatten()
            ->unique()
            ->sort();

        if ($imports->count() === 1) {
            return $imports->all();
        }

        return $imports
            ->add('union')
            ->unique()
            ->sort()
            ->all();
    }

    public function constraint(): string
    {
        $types = $this->types->map(fn(ParserTypeContract $type) => $type->constraint())
            ->unique()
            ->sort();

        if ($types->count() === 1) {
            return $types->firstOrFail();
        }

        return sprintf('union(%s)', $types->join(', '));
    }
}
