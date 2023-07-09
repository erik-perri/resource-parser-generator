<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ParserData
{
    /**
     * @var ReadOnlyCollection<string, ParserTypeContract>
     */
    public readonly ReadOnlyCollection $properties;

    /**
     * @param ResourceData $resource
     * @param ParserConfiguration $configuration
     * @param Collection<string, ParserTypeContract> $properties
     */
    public function __construct(
        public readonly ResourceData $resource,
        public readonly ParserConfiguration $configuration,
        Collection $properties,
    ) {
        $this->properties = new ReadOnlyCollection($properties->all());
    }
}
