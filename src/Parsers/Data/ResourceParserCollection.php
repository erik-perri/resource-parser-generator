<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;

class ResourceParserCollection
{
    /**
     * @var Collection<int, ResourceParserData>
     */
    private readonly Collection $parsers;

    public function __construct(ResourceParserData ...$parsers)
    {
        $this->parsers = collect();

        array_map(fn(ResourceParserData $parser) => $this->add($parser), $parsers);
    }

    public function add(ResourceParserData $parser): self
    {
        $this->parsers->add($parser);

        return $this;
    }

    /**
     * @return Collection<int, ResourceParserData>
     */
    public function collect(): Collection
    {
        return $this->parsers->collect();
    }

    public function has(string $fullyQualifiedResourceName, string $methodName): bool
    {
        return $this->parsers->some(
            fn(ResourceParserData $parser) => $parser->fullyQualifiedResourceName() === $fullyQualifiedResourceName
                && $parser->methodName() === $methodName,
        );
    }
}
