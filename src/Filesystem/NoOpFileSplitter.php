<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Filesystem\Contracts\ParserFileSplitterContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;

class NoOpFileSplitter implements ParserFileSplitterContract
{
    public function __construct(private readonly string $fileName)
    {
        //
    }

    public function split(ResourceParserCollection $parsers): Collection
    {
        return collect([
            $this->fileName => $parsers,
        ]);
    }
}
