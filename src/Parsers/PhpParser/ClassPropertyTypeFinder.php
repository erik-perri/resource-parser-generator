<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Mixed_;
use ReflectionClass;
use ReflectionException;
use ReflectionUnionType;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\Context\ClassScope;

class ClassPropertyTypeFinder
{
    public function __construct(
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
    ) {
        //
    }

    /**
     * @return string[]
     * @throws ReflectionException
     */
    public function find(ClassScope $scope, string $propertyName): array
    {
        $reflectionClass = new ReflectionClass($scope->fullyQualifiedClassName());
        $reflectionProperty = $reflectionClass->getProperty($propertyName);

        if ($reflectionProperty->getDocComment()) {
            $docBlock = $this->docBlockFactory->create($reflectionProperty->getDocComment());

            $vars = $docBlock->getTagsByName('var');
            $type = count($vars) ? $vars[0] : null;

            if ($type) {
                $type = $type instanceof TagWithType
                    ? $type->getType()
                    : new Mixed_();

                return $this->convertDocblockTagTypes->convert($type, $scope);
            }
        }

        $returnType = $reflectionProperty->getType();
        if ($returnType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($returnType->getTypes() as $type) {
                $types[] = $type->getName();
            }
        } else {
            if ($returnType) {
                // @phpstan-ignore-next-line -- ReflectionType::getName() is not missing
                $types = [$returnType->getName()];
            } else {
                $types = ['mixed'];
            }
        }

        return $types;
    }
}
