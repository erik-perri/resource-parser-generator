<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use BenSampo\Enum\Enum;
use Closure;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Parsers\Data\EnumScope;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class converts a ClassType which actually represents an enum into an EnumType.
 */
class EnumTypeConverter
{
    public function __construct(
        private readonly ClassParserContract $classParser,
    ) {
        //
    }

    public function convert(TypeContract $type): TypeContract
    {
        return $this->processChildTypes($type, function (TypeContract $type) {
            if (!($type instanceof Types\ClassType)) {
                return $type;
            }

            $typeScope = $this->classParser->parseType($type);

            // Convert any enums into enum types containing their backed types
            if ($typeScope instanceof EnumScope) {
                $type = $typeScope->propertyType('value');
                if (!$type) {
                    throw new RuntimeException(
                        sprintf('Unexpected enum "%s" without type', $typeScope->name()),
                    );
                }

                return new Types\EnumType($typeScope->fullyQualifiedName(), $type);
            } elseif ($typeScope->hasParent(Enum::class)) {
                $constants = $typeScope->constants();
                if ($constants->isEmpty()) {
                    throw new RuntimeException('Unexpected legacy enum without constants');
                }

                return new Types\EnumType($typeScope->fullyQualifiedName(), $constants->firstOrFail()->type());
            }

            return $type;
        });
    }

    /**
     * Since the enum type can actually be a child of a number of complex types we need to recursively process the
     * children of the type to ensure that we convert any enums that we find.
     *
     * @param TypeContract $type
     * @param Closure $callback
     * @return TypeContract
     */
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
