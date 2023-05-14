<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ClassScopeContract as ClassScopeContract;
use ResourceParserGenerator\Parsers\Data\ParsedResource;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ResourceReturnParser
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
     * @param Collection<string, ParsedResource>|null $parsedResources
     * @return Collection<string, ParsedResource>
     */
    public function parse(string $className, string $methodName, Collection $parsedResources = null): Collection
    {
        $parsedResources ??= collect();
        $parserKey = $this->getParserKey($className, $methodName);
        if ($parsedResources->has($parserKey)) {
            return $parsedResources;
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

                $childParserKey = $this->getParserKey($type->fullyQualifiedName(), $format);
                if ($parsedResources->has($childParserKey)) {
                    continue;
                }

                $this->parse($type->fullyQualifiedName(), $format, $parsedResources);

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

        $parsedResources->put($parserKey, ParsedResource::create($className, $methodName, $returnType));

        return $parsedResources;
    }

    private function getParserKey(string $className, string $methodName): string
    {
        return $className . '::' . $methodName;
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
