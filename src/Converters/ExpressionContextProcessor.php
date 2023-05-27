<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use BenSampo\Enum\Enum;
use Closure;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Parsers\Data\EnumScope;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

// TODO Find a better location for this class
class ExpressionContextProcessor
{
    public function __construct(
        private readonly ClassParserContract $classParser,
    ) {
        //
    }

    public function process(TypeContract $type, ConverterContext $context): TypeContract
    {
        $type = $this->processChildTypes($type, function (TypeContract $type) use ($context) {
            // If we're not a class or already a processed class we don't need to do anything
            if (!($type instanceof Types\ClassType) || $type instanceof Types\ClassWithMethodType) {
                return $type;
            }

            $returnScope = $this->classParser->parseType($type);

            // Convert any enums into their backed types
            if ($returnScope instanceof EnumScope) {
                $type = $returnScope->propertyType('value');
                if (!$type) {
                    throw new RuntimeException(
                        sprintf('Unexpected enum "%s" without type', $returnScope->name()),
                    );
                }

                return $type;
            } elseif ($returnScope->hasParent(Enum::class)) {
                $constants = $returnScope->constants();
                if ($constants->isEmpty()) {
                    throw new RuntimeException('Unexpected legacy enum without constants');
                }

                return $constants->firstOrFail()->type();
            }

            // Convert any resource classes into a ClassWithMethodType containing their format
            if ($returnScope->hasParent(Resource::class)) {
                if ($context->formatMethod()) {
                    $typeWithMethod = new Types\ClassWithMethodType(
                        $type->fullyQualifiedName(),
                        $type->alias(),
                        $context->formatMethod(),
                    );

                    $context->setFormatMethod(null);

                    return $typeWithMethod;
                }

                $defaultFormat = $this->findDefaultFormat($returnScope);
                if ($defaultFormat) {
                    return new Types\ClassWithMethodType(
                        $type->fullyQualifiedName(),
                        $type->alias(),
                        $defaultFormat,
                    );
                }
            }

            return $type;
        });

        if ($context->isCollection()) {
            $type = new Types\ArrayType(null, $type);

            $context->setIsCollection(false);
        }

        return $type;
    }

    private function findDefaultFormat(ClassScopeContract $resourceClass): string|null
    {
        if (!$resourceClass->hasParent(Resource::class)) {
            return null;
        }

        foreach ($resourceClass->methods() as $methodName => $methodScope) {
            $attribute = $methodScope->attribute(IsDefault::class);
            if ($attribute) {
                return $methodName;
            }
        }

        return null;
    }

    private function processChildTypes(TypeContract $type, Closure $callback): TypeContract
    {
        $updatedType = $callback($type);
        if ($updatedType !== $type) {
            return $updatedType;
        }

        if ($type instanceof Types\IntersectionType) {
            $types = [];
            foreach ($type->types() as $intersectionType) {
                $types[] = $this->processChildTypes($intersectionType, $callback);
            }

            return new Types\IntersectionType(...$types);
        }

        if ($type instanceof Types\UnionType) {
            $types = [];
            foreach ($type->types() as $unionType) {
                $types[] = $this->processChildTypes($unionType, $callback);
            }

            return new Types\UnionType(...$types);
        }

        if ($type instanceof Types\ArrayType) {
            $keys = $type->keys
                ? $this->processChildTypes($type->keys, $callback)
                : null;
            $values = $type->values
                ? $this->processChildTypes($type->values, $callback)
                : null;
            return new Types\ArrayType($keys, $values);
        }

        return $type;
    }
}
