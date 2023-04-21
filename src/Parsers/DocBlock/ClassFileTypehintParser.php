<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DocBlock;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Parsers\ResolveScope;

class ClassFileTypehintParser
{
    public function __construct(
        private readonly ResolveScope $resolveScope,
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
    ) {
        //
    }

    /**
     * @throws ReflectionException
     */
    public function parse(string $className, string $classFile): ClassTypehints
    {
        $typehints = new ClassTypehints($className);

        $reflectionClass = new ReflectionClass($className);
        $docComment = $reflectionClass->getDocComment();
        if (!$docComment) {
            return $typehints;
        }

        $this->resolveScope->loadImports($classFile);

        $docBlock = $this->docBlockFactory->create($docComment);

        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof DocBlock\Tags\Property || $tag instanceof DocBlock\Tags\PropertyRead) {
                $typehints = $typehints->addProperty(
                    $tag->getVariableName(),
                    $this->convertDocblockTagTypes->convert(
                        $tag->getType(),
                        $this->resolveScope,
                    ),
                );
            }

            if ($tag instanceof DocBlock\Tags\Method) {
                $typehints = $typehints->addMethod(
                    $tag->getMethodName(),
                    $this->convertDocblockTagTypes->convert(
                        $tag->getReturnType(),
                        $this->resolveScope,
                    ),
                );
            }
        }

        return $typehints;
    }
}
