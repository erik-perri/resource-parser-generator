<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;

class ResourceParserCollection
{
    /**
     * @var Collection<string, Collection<string, ResourceParserData>>
     */
    private readonly Collection $parsers;

    public function __construct(ResourceParserData ...$parsers)
    {
        $this->parsers = collect();

        array_map(fn(ResourceParserData $parser) => $this->add($parser), $parsers);
    }

    public function add(ResourceParserData $parser): self
    {
        $resourceParsers = $this->parsers->get($parser->fullyQualifiedResourceName()) ?? collect();

        $resourceParsers->put($parser->methodName(), $parser);

        $this->parsers->put($parser->fullyQualifiedResourceName(), $resourceParsers);

        return $this;
    }

    /**
     * @return Collection<string, Collection<string, ResourceParserData>>
     */
    public function collect(): Collection
    {
        return $this->parsers->collect();
    }

    /**
     * @return Collection<string, ResourceParserData>
     */
    public function flatten(): Collection
    {
        /**
         * @var Collection<string, ResourceParserData>
         */
        return $this->parsers->flatten();
    }

    public function has(string $fullyQualifiedResourceName, string $methodName): bool
    {
        return $this->parsers->has($fullyQualifiedResourceName)
            && $this->parsers->get($fullyQualifiedResourceName)?->has($methodName);
    }
}
