<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceMethodParserContract;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Types;
use RuntimeException;

class ResourceMethodParser implements ResourceMethodParserContract
{
    public function __construct(
        private readonly ClassMethodReturnParserContract $classMethodReturnParser,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<int, ResourceData> $parsedResources
     * @return Collection<int, ResourceData>
     */
    public function parse(string $className, string $methodName, Collection $parsedResources): Collection
    {
        if ($parsedResources->first(
            fn(ResourceData $resource) => $resource->className === $className && $resource->methodName === $methodName,
        )) {
            return $parsedResources;
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

        foreach ($returnType->properties() as $property) {
            $types = $property instanceof TypeWithChildrenContract
                ? $property->children()->add($property)
                : collect([$property]);

            foreach ($types as $type) {
                if ($type instanceof Types\ClassWithMethodType) {
                    $parsedResources = $this->parse($type->fullyQualifiedName(), $type->methodName(), $parsedResources);
                }
            }
        }

        return $parsedResources->add(new ResourceData(
            $className,
            $methodName,
            $returnType->properties(),
        ));
    }
}
