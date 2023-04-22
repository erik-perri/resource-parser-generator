<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DocBlock;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use ResourceParserGenerator\Parsers\PhpParser\Context\ResolverContract;
use RuntimeException;

class DocBlockTagTypeConverter
{
    /**
     * @return string[]
     */
    public function convert(?Type $type, ResolverContract $resolver): array
    {
        if ($type instanceof Compound) {
            $typehint = [];

            foreach ($type as $subType) {
                $typehint[] = $this->getTypehint($subType, $resolver);
            }

            return $typehint;
        }

        if (!$type) {
            return ['mixed'];
        }

        return [$this->getTypehint($type, $resolver)];
    }

    private function getTypehint(Type $type, ResolverContract $resolver): string
    {
        if (!method_exists($type, '__toString')) {
            throw new RuntimeException('Unexpected non-stringable property type: "' . get_class($type) . '"');
        }

        $typeString = ltrim($type->__toString(), '\\');

        return $resolver->resolveClass($typeString);
    }
}
