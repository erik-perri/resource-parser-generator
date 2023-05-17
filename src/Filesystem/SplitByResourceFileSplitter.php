<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Filesystem\ParserFileSplitterContract;
use ResourceParserGenerator\Generators\ParserNameGenerator;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;

class SplitByResourceFileSplitter implements ParserFileSplitterContract
{
    public function __construct(private readonly ParserNameGenerator $parserNameGenerator)
    {
        //
    }

    public function split(ResourceParserCollection $parsers): Collection
    {
        /**
         * @var Collection<string, ResourceParserCollection>
         */
        return $parsers->collect()->mapWithKeys(function (Collection $resourceParsers, string $resourceClass) {
            $fileName = $this->parserNameGenerator->generateFileName($resourceClass);
            $collection = new ResourceParserCollection(...$resourceParsers->all());

            return [
                $fileName => $collection,
            ];
        });
    }
}
