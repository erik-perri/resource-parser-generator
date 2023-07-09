<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodNullType implements ParserTypeContract
{
    public function constraint(ParserGeneratorContextContract $context): string
    {
        return 'z.null()';
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        return new ImportCollection(new Import('z', 'zod'));
    }
}
