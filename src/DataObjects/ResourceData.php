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
        public readonly ResourceConfiguration $configuration,
    ) {
        //
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

    public function updateConfiguration(ResourceConfiguration $configuration): self
    {
        return resolve(self::class, [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'properties' => $this->properties,
            'configuration' => $configuration,
        ]);
    }
}
