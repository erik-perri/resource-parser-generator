<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;

class ResourceGeneratorConfiguration
{
    /**
     * @var Collection<int, ResourceConfiguration>
     */
    private readonly Collection $parsers;

    public function __construct(
        public readonly string $outputPath,
        ResourceConfiguration ...$parsers,
    ) {
        $this->parsers = collect(array_values($parsers));
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceConfiguration|null
     */
    public function parser(string $className, string $methodName): ?ResourceConfiguration
    {
        return $this->parsers->first(
            fn(ResourceConfiguration $configuration) => $configuration->is($className, $methodName),
        );
    }

    /**
     * @return Collection<int, ResourceConfiguration>
     */
    public function parsers(): Collection
    {
        return $this->parsers->collect();
    }
}
