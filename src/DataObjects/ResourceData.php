<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ResourceData
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<string, ParserTypeContract> $properties
     * @param ResourceConfiguration $configuration
     */
    public function __construct(
        private readonly string $className,
        private readonly string $methodName,
        private readonly Collection $properties,
        private readonly ResourceConfiguration $configuration,
    ) {
        //
    }

    /**
     * @return ResourceConfiguration
     */
    public function configuration(): ResourceConfiguration
    {
        return $this->configuration;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    public function methodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return Collection<string, ParserTypeContract>
     */
    public function properties(): Collection
    {
        // TODO Make this immutable again
        return $this->properties;
    }
}
