<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Generators\Contracts\ParserNameGeneratorContract;

class ResourceParserData
{
    /**
     * @param string $fullyQualifiedResourceName
     * @param string $methodName
     * @param Collection<string, ParserTypeContract> $properties
     * @param ParserNameGeneratorContract $parserNameGenerator
     */
    public function __construct(
        private readonly string $fullyQualifiedResourceName,
        private readonly string $methodName,
        private readonly Collection $properties,
        private readonly ParserNameGeneratorContract $parserNameGenerator,
    ) {
        //
    }

    /**
     * @param string $fullyQualifiedResourceName
     * @param string $methodName
     * @param Collection<string, ParserTypeContract> $properties
     * @return self
     */
    public static function create(
        string $fullyQualifiedResourceName,
        string $methodName,
        Collection $properties,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedResourceName' => $fullyQualifiedResourceName,
            'methodName' => $methodName,
            'properties' => $properties,
        ]);
    }

    public function fullyQualifiedResourceName(): string
    {
        return $this->fullyQualifiedResourceName;
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
        return $this->properties->collect();
    }

    public function typeName(): string
    {
        return $this->parserNameGenerator->generateTypeName(
            $this->fullyQualifiedResourceName(),
            $this->methodName(),
        );
    }

    public function variableName(): string
    {
        return $this->parserNameGenerator->generateVariableName(
            $this->fullyQualifiedResourceName(),
            $this->methodName(),
        );
    }
}
