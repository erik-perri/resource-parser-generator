<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;

class ParserConfigurationGenerator
{
    public function __construct(
        private readonly ParserNameGeneratorContract $nameGenerator,
    ) {
        //
    }

    /**
     * @param ParserGeneratorConfiguration $generatorConfiguration
     * @param class-string $className
     * @param string $methodName
     * @return ParserConfiguration
     */
    public function generate(
        ParserGeneratorConfiguration $generatorConfiguration,
        string $className,
        string $methodName,
    ): ParserConfiguration {
        $configuration = $generatorConfiguration->parser($className, $methodName)
            ?? new ParserConfiguration([$className, $methodName]);

        $parserFile = $configuration->parserFile
            ?? $this->nameGenerator->generateFileName($configuration->method[0]);
        $typeName = $configuration->typeName
            ?? $this->nameGenerator->generateTypeName($configuration->method[0], $configuration->method[1]);
        $variableName = $configuration->variableName
            ?? $this->nameGenerator->generateVariableName($configuration->method[0], $configuration->method[1]);

        return new ParserConfiguration($configuration->method, $parserFile, $typeName, $variableName);
    }
}
