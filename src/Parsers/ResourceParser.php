<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\ClassScopeContract as ClassScopeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ResourceParser
{
    public function __construct(
        private readonly ClassParser $classParser,
        private readonly ClassMethodReturnParser $classMethodReturnParser,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserCollection|null $result
     * @return ResourceParserCollection
     */
    public function parse(
        string $className,
        string $methodName,
        ResourceParserCollection $result = null
    ): ResourceParserCollection {
        $result ??= new ResourceParserCollection();
        if ($result->has($className, $methodName)) {
            return $result;
        }

        $returnType = $this->parseReturnType($className, $methodName);

        $properties = $returnType->properties();

        foreach ($properties as $key => $type) {
            if ($type instanceof Types\ClassType) {
                $returnClass = $this->classParser->parse($type->fullyQualifiedName());

                if (!$returnClass->hasParent(Resource::class)) {
                    throw new RuntimeException(
                        sprintf(
                            'Unexpected non-resource class return "%s" in resource for property "%s"',
                            $type->fullyQualifiedName(),
                            $key,
                        ),
                    );
                }

                $format = $type instanceof Types\ClassWithMethodType
                    ? $type->methodName()
                    : $this->findDefaultFormat($returnClass);
                if (!$format) {
                    throw new RuntimeException(
                        sprintf(
                            'Unable to determine format for resource class "%s" in resource for property "%s"',
                            $type->fullyQualifiedName(),
                            $key,
                        ),
                    );
                }

                if ($result->has($type->fullyQualifiedName(), $format)) {
                    continue;
                }

                $this->parse($type->fullyQualifiedName(), $format, $result);

                // Replace the property type with a method type reference if we didn't have a reference, then we don't
                // need to parse the default format again later.
                if (!($type instanceof Types\ClassWithMethodType)) {
                    $returnType = new Types\ArrayWithPropertiesType(
                        $returnType->properties()
                            ->except($key)
                            ->put($key, new Types\ClassWithMethodType(
                                $type->fullyQualifiedName(),
                                $type->alias(),
                                $format,
                            )),
                    );
                }
            }
        }

        $result->add(ResourceParserData::create(
            $className,
            $methodName,
            $returnType->properties()->map(fn(TypeContract $property) => $property->parserType())
        ));

        return $result;
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return Types\ArrayWithPropertiesType
     */
    private function parseReturnType(string $className, string $methodName): Types\ArrayWithPropertiesType
    {
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

        return $returnType;
    }

    private function findDefaultFormat(ClassScopeContract $resourceClass): string|null
    {
        foreach ($resourceClass->methods() as $methodName => $methodScope) {
            $attribute = $methodScope->attribute(IsDefault::class);
            if ($attribute) {
                return $methodName;
            }
        }

        return null;
    }
}
