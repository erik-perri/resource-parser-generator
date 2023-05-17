<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;

interface ParserFileSplitterContract
{
    /**
     * @param ResourceParserCollection $parsers
     * @return Collection<string, array<int, ResourceParserData>>
     */
    public function split(ResourceParserCollection $parsers): Collection;
}
