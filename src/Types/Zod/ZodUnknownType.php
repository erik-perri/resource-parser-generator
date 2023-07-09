<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\Types\Traits\HasCommentTrait;

class ZodUnknownType implements ParserTypeContract, ParserTypeWithCommentContract
{
    use HasCommentTrait;

    public function constraint(ParserGeneratorContextContract $context): string
    {
        return 'unknown()';
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        return new ImportCollection(new Import('unknown', 'zod'));
    }
}
