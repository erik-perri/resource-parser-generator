<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;

// TODO Find a better location for this class
class ExpressionContextProcessor
{
    public function process(TypeContract $type, ConverterContext $context): TypeContract
    {
        if ($type instanceof Types\ClassType && $context->formatMethod()) {
            $type = new Types\ClassWithMethodType(
                $type->fullyQualifiedName(),
                $type->alias(),
                $context->formatMethod(),
            );

            $context->setFormatMethod(null);
        }

        if ($context->isCollection()) {
            $type = new Types\ArrayType(null, $type);

            $context->setIsCollection(false);
        }

        return $type;
    }
}
