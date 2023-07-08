<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;

interface ParserTypeContract
{
    public function constraint(ResourceGeneratorContextContract $context): string;

    public function imports(ResourceGeneratorContextContract $context): ImportCollectionContract;
}
