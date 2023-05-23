<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use RuntimeException;

class ZodArrayType implements ParserTypeContract
{
    public function __construct(
        public readonly ParserTypeContract|null $keys,
        public readonly ParserTypeContract|null $values,
    ) {
        //
    }

    public function constraint(): string
    {
        if ($this->keys && $this->values) {
            return sprintf('record(%s, %s)', $this->keys->constraint(), $this->values->constraint());
        }

        if ($this->values) {
            return sprintf('array(%s)', $this->values->constraint());
        }

        throw new RuntimeException('Untyped Zod arrays are not supported');
    }

    public function imports(): array
    {
        $constraintType = $this->keys && $this->values
            ? 'record'
            : 'array';

        $imports = collect(['zod' => [$constraintType]]);

        if ($this->keys) {
            $imports = $imports->mergeRecursive($this->keys->imports());
        }

        if ($this->values) {
            $imports = $imports->mergeRecursive($this->values->imports());
        }

        return $imports
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->all();
    }
}
