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
     * @param Collection<int, ParserData> $parsers
     */
    public function __construct(private readonly Collection $parsers)
    {
        //
    }

    public function find(string $className, string $methodName): ParserData|null
    {
        return $this->parsers->first(
            fn(ParserData $context) => $context->resource->className === $className
                && $context->resource->methodName === $methodName,
        );
    }
}
