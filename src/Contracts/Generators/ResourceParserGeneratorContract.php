<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;

interface ResourceParserGeneratorContract
{
    public function generate(ResourceParserContextCollection $parsers): string;
}
