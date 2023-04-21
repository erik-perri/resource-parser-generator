<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DocBlock;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use ResourceParserGenerator\Parsers\ResolveScope;
use RuntimeException;

class DocBlockTagTypeConverter
{
    public function convert(?Type $type, ResolveScope $scope): array
    {
        if ($type instanceof Compound) {
            $typehint = [];

            foreach ($type as $subType) {
                $typehint[] = $this->getTypehint($subType, $scope);
            }

            return $typehint;
        }

        return [$this->getTypehint($type, $scope)];
    }

    private function getTypehint(?Type $type, ResolveScope $scope): string
    {
        if (!method_exists($type, '__toString')) {
            throw new RuntimeException('Unexpected non-stringable property type: ' . get_class($type));
        }

        $typeString = ltrim($type->__toString(), '\\');

        return $scope->resolveClass($typeString);
    }
}
