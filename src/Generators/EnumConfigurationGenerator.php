<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumConfiguration;
use ResourceParserGenerator\DataObjects\EnumGeneratorConfiguration;

class EnumConfigurationGenerator
{
    public function __construct(
        private readonly EnumNameGeneratorContract $nameGenerator,
    ) {
        //
    }

    /**
     * @param EnumGeneratorConfiguration $generatorConfiguration
     * @param class-string $className
     * @return EnumConfiguration
     */
    public function generate(EnumGeneratorConfiguration $generatorConfiguration, string $className): EnumConfiguration
    {
        $configuration = $generatorConfiguration->enum($className) ?? new EnumConfiguration($className);

        $parserFile = $configuration->parserFile
            ?? $this->nameGenerator->generateFileName($configuration->className);
        $typeName = $configuration->typeName
            ?? $this->nameGenerator->generateTypeName($configuration->className);

        return new EnumConfiguration($configuration->className, $parserFile, $typeName);
    }
}
