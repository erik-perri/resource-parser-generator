<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use RuntimeException;

class ParserDataCollection
{
    /**
     * @var ReadOnlyCollection<int, ParserData>
     */
    public readonly ReadOnlyCollection $parsers;

    /**
     * @param Collection<int, ParserData> $parsers
     */
    public function __construct(Collection $parsers)
    {
        $this->parsers = new ReadOnlyCollection($parsers->all());
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ?ParserData
     */
    public function find(string $className, string $methodName): ?ParserData
    {
        return $this->parsers->first(
            fn(ParserData $parser) => $parser->resource->className === $className
                && $parser->resource->methodName === $methodName,
        );
    }

    /**
     * @return Collection<string, Collection<int, ParserData>>
     */
    public function splitToFiles(): Collection
    {
        /**
         * @var Collection<string, Collection<int, ParserData>>
         */
        return $this->parsers->collect()->groupBy(function (ParserData $data) {
            if (!$data->configuration->parserFile) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $data->configuration->method[0],
                    $data->configuration->method[1],
                ));
            }

            return $data->configuration->parserFile;
        });
    }
}
