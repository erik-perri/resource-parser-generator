<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceMethodParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
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

        foreach ($returnType->properties() as $type) {
            $parsedResources = $this->parseDependentResources($type, $parsedResources);
        }

        return $parsedResources->add(new ResourceData(
            $className,
            $methodName,
            $returnType->properties(),
        ));
    }

    /**
     * @param TypeContract $type
     * @param Collection<int, ResourceData> $parsedResources
     * @return Collection<int, ResourceData>
     */
    private function parseDependentResources(TypeContract $type, Collection $parsedResources): Collection
    {
        if ($type instanceof Types\ClassWithMethodType) {
            return $this->parse($type->fullyQualifiedName(), $type->methodName(), $parsedResources);
        }

        if ($type instanceof Types\UnionType) {
            foreach ($type->types() as $type) {
                $parsedResources = $this->parseDependentResources($type, $parsedResources);
            }
            return $parsedResources;
        }

        if ($type instanceof Types\ArrayWithPropertiesType) {
            foreach ($type->properties() as $type) {
                $parsedResources = $this->parseDependentResources($type, $parsedResources);
            }
            return $parsedResources;
        }

        if ($type instanceof Types\ArrayType) {
            if ($type->keys) {
                $parsedResources = $this->parseDependentResources($type->keys, $parsedResources);
            }
            if ($type->values) {
                $parsedResources = $this->parseDependentResources($type->values, $parsedResources);
            }
            return $parsedResources;
        }

        return $parsedResources;
    }
}
