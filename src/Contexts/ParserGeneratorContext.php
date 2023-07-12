<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ParserData;

/**
 * This class is used to track the local scope when generating the parser files.
 */
class ParserGeneratorContext implements ParserGeneratorContextContract
{
    /**
     * @param Collection<int, ParserData> $localParsers
     * @param Collection<int, ParserData> $globalParsers
     */
    public function __construct(
        private readonly Collection $localParsers,
        private readonly Collection $globalParsers,
    ) {
        //
    }

    public function findGlobal(string $className, string $methodName): ParserData|null
    {
        return $this->globalParsers->first(
            fn(ParserData $context) => $context->resource->className === $className
                && $context->resource->methodName === $methodName,
        );
    }

    public function findLocal(string $className, string $methodName): ParserData|null
    {
        return $this->localParsers->first(
            fn(ParserData $context) => $context->resource->className === $className
                && $context->resource->methodName === $methodName,
        );
    }
}
