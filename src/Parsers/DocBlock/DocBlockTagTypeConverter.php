<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DocBlock;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Nullable;
use PhpParser\Node\Name;
use ResourceParserGenerator\Parsers\PhpParser\Context\ResolverContract;
use RuntimeException;

class DocBlockTagTypeConverter
{
    /**
     * @return string[]
     */
    public function convert(?Type $type, ResolverContract $resolver): array
    {
        if (!$type) {
            return ['mixed'];
        }

        if ($type instanceof Compound) {
            $typehints = [];

            foreach ($type as $subType) {
                $typehints[] = $this->getTypehint($subType, $resolver);
            }

            return array_unique(array_merge(...$typehints));
        }

        return $this->getTypehint($type, $resolver);
    }

    /**
     * @return string[]
     */
    private function getTypehint(Type $type, ResolverContract $resolver): array
    {
        if (!method_exists($type, '__toString')) {
            throw new RuntimeException('Unexpected non-stringable property type: "' . get_class($type) . '"');
        }

        if ($type instanceof Nullable) {
            return array_unique(array_merge(
                ['null'],
                $this->getTypehint($type->getActualType(), $resolver),
            ));
        }

        $typeString = ltrim($type->__toString(), '\\');

        if (in_array($typeString, [
            'int',
            'float',
            'bool',
            'string',
            'array',
            'null',
            'void',
        ], true)) {
            return [$typeString];
        }

        return [$resolver->resolveClass(new Name($typeString))];
    }
}
