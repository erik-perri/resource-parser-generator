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

    public function constraint(): string
    {
        $types = $this->types->map(fn(ParserTypeContract $type) => $type->constraint())
            ->unique()
            ->sort();

        if ($types->count() === 1) {
            return $types->firstOrFail();
        }

        return sprintf('union([%s])', $types->join(', '));
    }

    public function imports(): array
    {
        $imports = collect(['zod' => ['union']]);

        foreach ($this->types as $type) {
            $imports = $imports->mergeRecursive($type->imports());
        }

        if ($imports->count() === 1) {
            return $imports->all();
        }

        return $imports
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->all();
    }
}
