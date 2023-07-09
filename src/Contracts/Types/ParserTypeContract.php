<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;

interface ParserTypeContract
{
    public function constraint(ParserGeneratorContextContract $context): string;

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract;
}
