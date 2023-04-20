<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Actions;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use RuntimeException;

class ConvertDocblockTagTypesAction
{
    public function execute(?Type $type, array $imports): string|array
    {
        if ($type instanceof Compound) {
            $typehint = [];

            foreach ($type as $subType) {
                $typehint[] = $this->getTypehint($subType, $imports);
            }

            return $typehint;
        }

        return $this->getTypehint($type, $imports);
    }

    private function getTypehint(?Type $type, array $imports): string
    {
        if (!method_exists($type, '__toString')) {
            throw new RuntimeException('Unexpected non-stringable property type: ' . get_class($type));
        }

        $typeString = ltrim($type->__toString(), '\\');

        return array_key_exists($typeString, $imports)
            ? $imports[$typeString]
            : $typeString;
    }
}
