<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ResourceData
{
    /**
     * @var ReadOnlyCollection<string, ParserTypeContract>
     */
    public readonly ReadOnlyCollection $properties;

    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceConfiguration $configuration
     * @param Collection<string, ParserTypeContract> $properties
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        public readonly ResourceConfiguration $configuration,
        Collection $properties,
    ) {
        $this->properties = new ReadOnlyCollection($properties->all());
    }
}
