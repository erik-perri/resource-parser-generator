<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodNullableType implements ParserTypeContract
{
    public function __construct(
        private readonly ParserTypeContract $type,
    ) {
        //
    }

    public function imports(): ImportCollectionContract
    {
        return (new ImportCollection(new Import('nullable', 'zod')))
            ->merge($this->type->imports());
    }

    public function constraint(): string
    {
        return sprintf('nullable(%s)', $this->type->constraint());
    }
}
