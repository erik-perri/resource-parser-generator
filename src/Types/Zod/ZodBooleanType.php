<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodBooleanType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'boolean()';
    }

    public function imports(): ImportCollectionContract
    {
        return new ImportCollection(new Import('boolean', 'zod'));
    }
}
