<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodOptionalType implements ParserTypeContract
{
    public function __construct(
        private readonly ParserTypeContract $type,
    ) {
        //
    }

    public function imports(ResourceGeneratorContextContract $context): ImportCollectionContract
    {
        return (new ImportCollection(new Import('optional', 'zod')))
            ->merge($this->type->imports($context));
    }

    public function constraint(ResourceGeneratorContextContract $context): string
    {
        return sprintf('optional(%s)', $this->type->constraint($context));
    }
}
