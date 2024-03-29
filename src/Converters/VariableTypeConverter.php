<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ResourceParserGenerator\Contracts\Converters\VariableTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class takes a normal variable and converts it to a TypeContract.
 */
class VariableTypeConverter implements VariableTypeConverterContract
{
    public function convert(mixed $variable): TypeContract
    {
        return match (gettype($variable)) {
            'boolean' => new Types\BoolType(),
            'integer' => new Types\IntType(),
            'double' => new Types\FloatType(),
            'string' => new Types\StringType(),
            'array' => new Types\ArrayType(null, null),
            'object' => new Types\ObjectType(),
            'resource', 'resource (closed)' => throw new RuntimeException('Resource type is not supported'),
            'NULL' => new Types\NullType(),
            'unknown type' => new Types\MixedType(),
        };
    }
}
