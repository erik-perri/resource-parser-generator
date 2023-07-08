<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceGeneratorConfiguration;

class ParserConfigurationGenerator
{
    public function __construct(
        private readonly ParserNameGeneratorContract $nameGenerator,
    ) {
        //
    }

    /**
     * @param ResourceGeneratorConfiguration $configuration
     * @param class-string $className
     * @param string $methodName
     * @return ResourceConfiguration
     */
    public function generate(
        ResourceGeneratorConfiguration $configuration,
        string $className,
        string $methodName,
    ): ResourceConfiguration {
        $configuration = $configuration->parser($className, $methodName)
            ?? new ResourceConfiguration([$className, $methodName]);

        $parserFile = $configuration->parserFile
            ?? $this->nameGenerator->generateFileName($configuration->method[0]);
        $typeName = $configuration->typeName
            ?? $this->nameGenerator->generateTypeName($configuration->method[0], $configuration->method[1]);
        $variableName = $configuration->variableName
            ?? $this->nameGenerator->generateVariableName($configuration->method[0], $configuration->method[1]);

        return new ResourceConfiguration($configuration->method, $parserFile, $typeName, $variableName);
    }
}
