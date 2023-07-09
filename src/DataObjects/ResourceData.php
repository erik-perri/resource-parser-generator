<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class ResourceData
{
    /**
     * @var ReadOnlyCollection<string, TypeContract>
     */
    public readonly ReadOnlyCollection $properties;

    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<string, TypeContract> $properties
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        Collection $properties,
    ) {
        $this->properties = new ReadOnlyCollection($properties->all());
    }
}
