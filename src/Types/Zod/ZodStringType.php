<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodStringType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'string()';
    }

    public function imports(): ImportCollectionContract
    {
        return new ImportCollection(new Import('string', 'zod'));
    }
}
