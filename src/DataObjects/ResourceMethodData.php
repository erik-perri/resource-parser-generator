<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ResourceMethodData
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<string, ParserTypeContract> $properties
     */
    public function __construct(
        private readonly string $className,
        private readonly string $methodName,
        private readonly Collection $properties,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<string, ParserTypeContract> $properties
     * @return self
     */
    public static function create(string $className, string $methodName, Collection $properties): self
    {
        return resolve(self::class, [
            'className' => $className,
            'methodName' => $methodName,
            'properties' => $properties,
        ]);
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
