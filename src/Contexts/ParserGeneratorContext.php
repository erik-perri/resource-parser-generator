<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ParserData;

/**
 * This class is used to track the local scope when generating the parser files.
 */
class ParserGeneratorContext implements ParserGeneratorContextContract
{
    /**
     * @var Collection<int, ParserData>
     */
    private Collection $localParsers;

    /**
     * @param Collection<int, ParserData> $parsers
     */
    public function __construct(private readonly Collection $parsers)
    {
        $this->localParsers = collect();
    }

    public function find(string $className, string $methodName): ParserData|null
    {
        return $this->parsers->first(
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

    /**
     * @template T
     * @param Collection<int, ParserData> $localParsers
     * @param Closure(): T $callback
     * @return T
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed
    {
        $this->localParsers = $localParsers->collect();
        $result = $callback();
        $this->localParsers = collect();

        return $result;
    }
}
