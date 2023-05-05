<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ReflectionType;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;

class ReflectionTypeConverter
{
    public function convert(ReflectionType|null $type): TypeContract
    {
        if (!$type) {
            return new Types\UntypedType();
        }

        throw new RuntimeException(
            sprintf('Reflection type converter not implemented for "%s"', get_class($type)),
        );
    }
}
