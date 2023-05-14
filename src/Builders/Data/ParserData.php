<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

class ParserData
{
    /**
     * @param string $variableName
     * @param string $typeName
     * @param Collection<string, ParserTypeContract> $properties
     */
    public function __construct(
        private readonly string $variableName,
        private readonly string $typeName,
        private readonly Collection $properties,
    ) {
        //
    }

    /**
     * @return Collection<string, ParserTypeContract>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }

    public function typeName(): string
    {
        return $this->typeName;
    }

    public function variableName(): string
    {
        return $this->variableName;
    }
}
