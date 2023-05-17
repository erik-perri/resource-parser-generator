<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Filesystem\ParserFileSplitterContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;

class SplitByResourceFileSplitter implements ParserFileSplitterContract
{
    public function split(ResourceParserCollection $parsers): Collection
    {
        /**
         * @var Collection<string, array<int, ResourceParserData>>
         */
        $files = collect();

        foreach ($parsers->collect() as $parser) {
            $files = $files->mergeRecursive([
                $parser->fileName() => [$parser],
            ]);
        }

        return $files;
    }
}
