<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ZodArrayType implements ParserTypeContract
{
    public function __construct(
        private readonly ParserTypeContract|null $keys,
        private readonly ParserTypeContract|null $values,
    ) {
        //
    }

    public function imports(): array
    {
        $constraintType = $this->keys && $this->values
            ? 'record'
            : 'array';
        $imports = collect([$constraintType]);

        if ($this->keys) {
            $imports = $imports->merge($this->keys->imports());
        }

        if ($this->values) {
            $imports = $imports->merge($this->values->imports());
        }

        return $imports
            ->unique()
            ->sort()
            ->all();
    }

    public function constraint(): string
    {
        if ($this->keys && $this->values) {
            return sprintf('record(%s, %s)', $this->keys->constraint(), $this->values->constraint());
        }

        if ($this->keys) {
            return sprintf('array(%s)', $this->keys->constraint());
        }

        return 'array()';
    }
}
