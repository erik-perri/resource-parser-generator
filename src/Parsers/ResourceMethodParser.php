<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceParserContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Generators\ParserConfigurationGenerator;
use ResourceParserGenerator\Types;
use RuntimeException;

class ResourceMethodParser implements ResourceParserContract
{
    public function __construct(
        private readonly ClassMethodReturnParserContract $classMethodReturnParser,
        private readonly ParserTypeConverterContract $parserTypeConverter,
        private readonly ParserConfigurationGenerator $parserConfigurationGenerator,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceGeneratorContextContract $context
     * @return ResourceData
     */
    public function parse(
        string $className,
        string $methodName,
        ResourceGeneratorContextContract $context,
    ): ResourceData {
        if ($alreadyParsed = $context->findGlobal($className, $methodName)) {
            return $alreadyParsed;
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
            $this->parseDependentResources($type, $context);
        }

        $configuration = $this->parserConfigurationGenerator->generate(
            $context->configuration(),
            $className,
            $methodName,
        );

        $data = new ResourceData(
            $className,
            $methodName,
            $configuration,
            $returnType->properties()->map(fn(TypeContract $type) => $this->parserTypeConverter->convert($type)),
        );

        $context->add($data);

        return $data;
    }

    private function parseDependentResources(TypeContract $type, ResourceGeneratorContextContract $context): void
    {
        if ($type instanceof Types\ClassWithMethodType) {
            $this->parse($type->fullyQualifiedName(), $type->methodName(), $context);
        } elseif ($type instanceof Types\UnionType) {
            $type->types()->each(fn(TypeContract $type) => $this->parseDependentResources($type, $context));
        } elseif ($type instanceof Types\ArrayWithPropertiesType) {
            $type->properties()->each(fn(TypeContract $type) => $this->parseDependentResources($type, $context));
        } elseif ($type instanceof Types\ArrayType) {
            if ($type->keys) {
                $this->parseDependentResources($type->keys, $context);
            }
            if ($type->values) {
                $this->parseDependentResources($type->values, $context);
            }
        }
    }
}
