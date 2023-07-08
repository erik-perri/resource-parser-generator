<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;

class ResourceGeneratorConfiguration
{
    /**
     * @var Collection<int, ResourceConfiguration>
     */
    public readonly Collection $parsers;

    public function __construct(
        public readonly string $outputPath,
        ResourceConfiguration ...$parsers,
    ) {
        $this->parsers = collect(array_values($parsers));
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceConfiguration
     */
    public function parserConfiguration(string $className, string $methodName): ResourceConfiguration
    {
        $configuration = $this->parsers->first(
            fn(ResourceConfiguration $configuration) => $configuration->is($className, $methodName),
        ) ?? new ResourceConfiguration([$className, $methodName]);

        $generator = resolve(ParserNameGeneratorContract::class);

        $parserFile = $configuration->parserFile
            ?? $generator->generateFileName($configuration->method[0]);
        $typeName = $configuration->typeName
            ?? $generator->generateTypeName($configuration->method[0], $configuration->method[1]);
        $variableName = $configuration->variableName
            ?? $generator->generateVariableName($configuration->method[0], $configuration->method[1]);

        return new ResourceConfiguration(
            $configuration->method,
            $parserFile,
            $typeName,
            $variableName,
        );
    }
}
