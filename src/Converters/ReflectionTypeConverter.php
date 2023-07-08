<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ReflectionNamedType;
use ReflectionType;
use ResourceParserGenerator\Contracts\Converters\ReflectionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class takes a ReflectionType and converts it to a TypeContract.
 *
 * This is used to convert types we cannot infer any other way, like built in PHP method calls.
 */
class ReflectionTypeConverter implements ReflectionTypeConverterContract
{
    public function convert(ReflectionType|null $type): TypeContract
    {
        if (!$type) {
            return new Types\UntypedType();
        }

        if ($type instanceof ReflectionNamedType) {
            switch ($type->getName()) {
                case 'array':
                    return new Types\ArrayType(null, null);
                default:
                    break;
            }
        }

        throw new RuntimeException(
            sprintf('Reflection type converter not implemented for "%s"', get_class($type)),
        );
    }
}
