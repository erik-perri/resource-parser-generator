<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Actions;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;

class GetClassTypehintsFromFileAction
{
    public function __construct(
        private readonly GetUseStatementsFromFileAction $getImportsFromFile,
        private readonly DocBlockFactory $docBlockFactory,
        private readonly ConvertDocblockTagTypesAction $convertDocblockTagTypes,
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
                $typehints[$tag->getVariableName()] = $this->convertDocblockTagTypes->execute(
                    $tag->getType(),
                    $imports,
                );
            }

            if ($tag instanceof DocBlock\Tags\Method) {
                $typehints[$tag->getMethodName() . '()'] = $this->convertDocblockTagTypes->execute(
                    $tag->getReturnType(),
                    $imports,
                );
            }
        }

        return $typehints;
    }
}
