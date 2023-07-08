<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodBooleanType implements ParserTypeContract
{
    public function constraint(ResourceGeneratorContextContract $context): string
    {
        return 'boolean()';
    }

    public function imports(ResourceGeneratorContextContract $context): ImportCollectionContract
    {
        return new ImportCollection(new Import('boolean', 'zod'));
    }
}
