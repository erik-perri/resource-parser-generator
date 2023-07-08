<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ResourceGeneratorConfiguration
{
    /**
     * @var ReadOnlyCollection<int, ResourceConfiguration>
     */
    public readonly ReadOnlyCollection $parsers;

    public function __construct(
        public readonly string $outputPath,
        ResourceConfiguration ...$parsers,
    ) {
        $this->parsers = new ReadOnlyCollection(array_values($parsers));
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
}
