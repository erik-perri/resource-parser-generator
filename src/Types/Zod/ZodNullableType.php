<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodNullableType implements ParserTypeContract
{
    public function __construct(
        private readonly ParserTypeContract $type,
    ) {
        //
    }

    public function imports(): array
    {
        $imports = collect(['zod' => ['nullable']])->mergeRecursive($this->type->imports());

        return $imports
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->all();
    }

    public function constraint(): string
    {
        return sprintf('nullable(%s)', $this->type->constraint());
    }
}
