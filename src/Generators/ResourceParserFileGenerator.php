<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Filesystem\Contracts\ParserFileSplitterContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use RuntimeException;

class ResourceParserFileGenerator
{
    public function __construct(private readonly ResourceParserGenerator $resourceParserGenerator)
    {
        //
    }

    /**
     * @param ResourceParserCollection $parsers
     * @param ParserFileSplitterContract $fileSplitter
     * @return Collection<string, string>
     */
    public function generate(ResourceParserCollection $parsers, ParserFileSplitterContract $fileSplitter): Collection
    {
        /**
         * @var Collection<string, string>
         */
        $files = collect();

        $fileSplitter
            ->split($parsers)
            ->each(function (ResourceParserCollection $parsers, string $fileName) use ($files) {
                $contents = $this->resourceParserGenerator->generate($parsers);

                if ($files->has($fileName)) {
                    throw new RuntimeException(sprintf('File "%s" already exists in generation.', $fileName));
                }

                $files->put($fileName, $contents);
            });

        return $files;
    }
}
