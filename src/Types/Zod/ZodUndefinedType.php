<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodUndefinedType implements ParserTypeContract
{
    public function constraint(): string
    {
        return 'undefined()';
    }

    public function imports(): ImportCollectionContract
    {
        return new ImportCollection(new Import('undefined', 'zod'));
    }
}
