<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Actions;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class GetClassTypehintsFromFileAction
{
    public function __construct(
        private readonly GetUseStatementsFromFileAction $getImportsFromFile,
        private readonly DocBlockFactory $docBlockFactory,
    ) {
        //
    }

    /**
     * @throws ReflectionException
     */
    public function execute(string $className, string $classFile): array
    {
        $reflectionClass = new ReflectionClass($className);
        $docComment = $reflectionClass->getDocComment();
        if (!$docComment) {
            return [];
        }

        $docBlock = $this->docBlockFactory->create($docComment);
        $imports = $this->getImportsFromFile->execute($classFile);
        $typehints = [];

        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof DocBlock\Tags\Property || $tag instanceof DocBlock\Tags\PropertyRead) {
                $type = $tag->getType();

                if ($type instanceof Compound) {
                    $typehint = [];

                    foreach ($type as $subType) {
                        $typehint[] = $this->getTypehint($subType, $imports);
                    }
                } else {
                    $typehint = $this->getTypehint($type, $imports);
                }

                $typehints[$tag->getVariableName()] = $typehint;
            }
        }

        return $typehints;
    }

    public function getTypehint(Type $type, array $imports): string
    {
        if (!method_exists($type, '__toString')) {
            throw new RuntimeException('Unexpected non-stringable property type: ' . get_class($type));
        }

        $type = ltrim($type->__toString(), '\\');

        return array_key_exists($type, $imports)
            ? $imports[$type]
            : $type;
    }
}
