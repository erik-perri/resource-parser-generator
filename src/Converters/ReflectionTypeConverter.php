<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ReflectionNamedType;
use ReflectionType;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class ReflectionTypeConverter
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
