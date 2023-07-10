<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Processors;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Parsers\ResourceMethodParser;
use RuntimeException;
use Throwable;

class ResourceConfigurationProcessor
{
    public function __construct(private readonly ResourceMethodParser $resourceMethodParser)
    {
        //
    }

    /**
     * @param ParserGeneratorConfiguration $configuration
     * @return Collection<int, ResourceData>
     */
    public function process(ParserGeneratorConfiguration $configuration): Collection
    {
        $resourceCollection = collect();

        foreach ($configuration->parsers as $parserConfiguration) {
            try {
                $resourceCollection = $this->resourceMethodParser->parse(
                    $parserConfiguration->method[0],
                    $parserConfiguration->method[1],
                    $resourceCollection,
                );
            } catch (Throwable $error) {
                throw new RuntimeException(sprintf(
                    'Failed to parse resource "%s::%s"',
                    $parserConfiguration->method[0],
                    $parserConfiguration->method[1],
                ), 0, $error);
            }
        }

        return $resourceCollection;
    }
}
