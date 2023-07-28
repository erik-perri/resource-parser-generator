<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use BenSampo\Enum\Enum;
use Closure;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Parsers\Data\EnumScope;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class finalizes a converted TypeContract based on the collected context. Turning enums into their backed types,
 * and resource classes into references to their specified format method.
 */
class ConverterContextProcessor
{
    public function __construct(
        private readonly ClassParserContract $classParser,
    ) {
        //
    }

    public function process(TypeContract $type, ConverterContext $context): TypeContract
    {
        return $this->processChildTypes($type, function (TypeContract $type) {
            // If we're not a class or already a processed class we don't need to do anything
            if (!($type instanceof Types\ClassType) || $type instanceof Types\ClassWithMethodType) {
                return $type;
            }

            $returnScope = $this->classParser->parseType($type);

            // Convert any enums into enum types containing their backed types
            if ($returnScope instanceof EnumScope) {
                $type = $returnScope->propertyType('value');
                if (!$type) {
                    throw new RuntimeException(
                        sprintf('Unexpected enum "%s" without type', $returnScope->name()),
                    );
                }

                return new Types\EnumType($returnScope->fullyQualifiedName(), $type);
            } elseif ($returnScope->hasParent(Enum::class)) {
                $constants = $returnScope->constants();
                if ($constants->isEmpty()) {
                    throw new RuntimeException('Unexpected legacy enum without constants');
                }

                return new Types\EnumType($returnScope->fullyQualifiedName(), $constants->firstOrFail()->type());
            }

            return $type;
        });
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
