<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceParserContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Types;
use RuntimeException;

class ResourceMethodParser implements ResourceParserContract
{
    public function __construct(
        private readonly ClassMethodReturnParserContract $classMethodReturnParser,
        private readonly ResourceGeneratorContextContract $resourceParserRepository,
    ) {
        //
    }

    public function parse(
        string $className,
        string $methodName,
    ): ResourceData {
        if ($alreadyParsed = $this->resourceParserRepository->findGlobal($className, $methodName)) {
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
            $this->parseDependentResources($type);
        }

        $context = new ResourceData(
            $className,
            $methodName,
            $returnType->properties()->map(function (
                TypeContract $property,
                string $name,
            ) use (
                $className,
                $methodName,
            ) {
                try {
                    return $property->parserType();
                } catch (RuntimeException $exception) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to parse property "%s" in "%s::%s", %s',
                            $name,
                            $className,
                            $methodName,
                            $exception->getMessage(),
                        ),
                        previous: $exception,
                    );
                }
            }),
            new ResourceConfiguration($className, $methodName, null, null, null),
        );

        $this->resourceParserRepository->add($context);

        return $context;
    }

    private function parseDependentResources(TypeContract $type): void
    {
        if ($type instanceof Types\ClassWithMethodType) {
            $this->parse($type->fullyQualifiedName(), $type->methodName());
        } elseif ($type instanceof Types\UnionType) {
            $type->types()->each(fn(TypeContract $type) => $this->parseDependentResources($type));
        } elseif ($type instanceof Types\ArrayWithPropertiesType) {
            $type->properties()->each(fn(TypeContract $type) => $this->parseDependentResources($type));
        } elseif ($type instanceof Types\ArrayType) {
            if ($type->keys) {
                $this->parseDependentResources($type->keys);
            }
            if ($type->values) {
                $this->parseDependentResources($type->values);
            }
        }
    }
}
