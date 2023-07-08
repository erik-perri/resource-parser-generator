<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceMethodParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\DataObjects\ResourceDataCollection;
use ResourceParserGenerator\DataObjects\ResourceGeneratorConfiguration;
use ResourceParserGenerator\Generators\ParserConfigurationGenerator;
use ResourceParserGenerator\Types;
use RuntimeException;

class ResourceMethodParser implements ResourceMethodParserContract
{
    public function __construct(
        private readonly ClassMethodReturnParserContract $classMethodReturnParser,
        private readonly ParserTypeConverterContract $parserTypeConverter,
        private readonly ParserConfigurationGenerator $parserConfigurationGenerator,
    ) {
        //
    }

    public function parse(
        string $className,
        string $methodName,
        ResourceDataCollection $resources,
        ResourceGeneratorConfiguration $configuration,
    ): void {
        if ($resources->find($className, $methodName)) {
            return;
        }

        $returnType = $this->classMethodReturnParser->parse($className, $methodName);

        if (!($returnType instanceof Types\ArrayWithPropertiesType)) {
            throw new RuntimeException(
                sprintf(
                    'Unexpected return for method "%s" in class "%s", expected array received "%s"',
                    $methodName,
                    $className,
                    $returnType->describe(),
                ),
            );
        }

        foreach ($returnType->properties() as $type) {
            $this->parseDependentResources($type, $resources, $configuration);
        }

        $resources->add(new ResourceData(
            $className,
            $methodName,
            $this->parserConfigurationGenerator->generate($configuration, $className, $methodName),
            $returnType->properties()->map(fn(TypeContract $type) => $this->parserTypeConverter->convert($type)),
        ));
    }

    private function parseDependentResources(
        TypeContract $type,
        ResourceDataCollection $resources,
        ResourceGeneratorConfiguration $configuration,
    ): void {
        if ($type instanceof Types\ClassWithMethodType) {
            $this->parse($type->fullyQualifiedName(), $type->methodName(), $resources, $configuration);
        } elseif ($type instanceof Types\UnionType) {
            $type->types()->each(
                fn(TypeContract $type) => $this->parseDependentResources($type, $resources, $configuration),
            );
        } elseif ($type instanceof Types\ArrayWithPropertiesType) {
            $type->properties()->each(
                fn(TypeContract $type) => $this->parseDependentResources($type, $resources, $configuration),
            );
        } elseif ($type instanceof Types\ArrayType) {
            if ($type->keys) {
                $this->parseDependentResources($type->keys, $resources, $configuration);
            }
            if ($type->values) {
                $this->parseDependentResources($type->values, $resources, $configuration);
            }
        }
    }
}
