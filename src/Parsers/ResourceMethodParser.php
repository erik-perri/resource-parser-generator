<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use ResourceParserGenerator\Contracts\ClassScopeContract as ClassScopeContract;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceContext;
use ResourceParserGenerator\DataObjects\ResourceMethodData;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ResourceMethodParser implements ResourceParserContract
{
    public function __construct(
        private readonly ClassParserContract $classParser,
        private readonly ClassMethodReturnParserContract $classMethodReturnParser,
    ) {
        //
    }

    public function parse(
        string $className,
        string $methodName,
        ResourceParserContextCollection $parsed = null,
    ): ResourceParserContextCollection {
        $parsed ??= ResourceParserContextCollection::create(collect());
        if ($parsed->find($className, $methodName)) {
            return $parsed;
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

                if ($parsed->find($type->fullyQualifiedName(), $format)) {
                    continue;
                }

                $parsed = $this->parse($type->fullyQualifiedName(), $format, $parsed);

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

        $parserData = ResourceMethodData::create(
            $className,
            $methodName,
            $returnType->properties()->map(fn(TypeContract $property) => $property->parserType())
        );

        return $parsed->concat(new ResourceContext(
            new ResourceConfiguration($className, $methodName, null, null, null),
            $parserData,
        ));
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
