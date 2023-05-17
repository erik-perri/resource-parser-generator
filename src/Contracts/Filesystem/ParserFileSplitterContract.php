<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;

interface ParserFileSplitterContract
{
    /**
     * @param ResourceParserCollection $parsers
     * @return Collection<string, ResourceParserCollection>
     */
    public function split(ResourceParserCollection $parsers): Collection;
}
