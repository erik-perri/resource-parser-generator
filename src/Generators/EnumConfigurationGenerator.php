<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumConfiguration;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;

class EnumConfigurationGenerator
{
    public function __construct(
        private readonly EnumNameGeneratorContract $nameGenerator,
    ) {
        //
    }

    /**
     * @param ParserGeneratorConfiguration $generatorConfiguration
     * @param class-string $className
     * @return EnumConfiguration
     */
    public function generate(ParserGeneratorConfiguration $generatorConfiguration, string $className): EnumConfiguration
    {
        // TODO Load configured ?? new EnumConfiguration
        $configuration = new EnumConfiguration($className);

        $parserFile = $configuration->parserFile
            ?? $this->nameGenerator->generateFileName($configuration->className);
        $typeName = $configuration->typeName
            ?? $this->nameGenerator->generateTypeName($configuration->className);

        return new EnumConfiguration($configuration->className, $parserFile, $typeName);
    }
}
