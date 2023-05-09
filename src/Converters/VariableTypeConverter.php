<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class VariableTypeConverter
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
            'resource' => new Types\ResourceType(),
            'NULL' => new Types\NullType(),
            'unknown type' => new Types\MixedType(),
        };
    }
}
