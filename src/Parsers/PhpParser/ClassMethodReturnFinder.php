<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Void_;
use ReflectionClass;
use ReflectionException;
use ReflectionUnionType;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\Context\ClassScope;
use ResourceParserGenerator\Parsers\PhpParser\Context\MethodScope;
use ResourceParserGenerator\Parsers\PhpParser\Context\VirtualMethodScope;

class ClassMethodReturnFinder
{
    public function __construct(
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
        private readonly SimpleTypeConverter $typeConverter,
    ) {
        //
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    public function find(MethodScope|VirtualMethodScope $method): array
    {
        if ($method instanceof MethodScope) {
            $explicitType = $method->ast()->returnType;
            if ($explicitType) {
                return $this->typeConverter->convert($explicitType);
            }
        }

        return $this->findReturnsFromReflection($method->scope, $method->name());
    }

    /**
     * @return string[]
     * @throws ReflectionException
     */
    private function findReturnsFromReflection(ClassScope $classScope, string $method): array
    {
        $reflectionClass = new ReflectionClass($classScope->fullyQualifiedClassName());
        $reflectedMethod = $reflectionClass->getMethod($method);

        // Check the docblock for the method
        if ($reflectedMethod->getDocComment()) {
            $docBlock = $this->docBlockFactory->create($reflectedMethod->getDocComment());

            $returns = $docBlock->getTagsByName('return');
            $return = count($returns) ? $returns[0] : null;

            if ($return) {
                $type = $return instanceof TagWithType
                    ? $return->getType()
                    : new Compound([new Mixed_(), new Void_()]);

                return $this->convertDocblockTagTypes->convert($type, $classScope);
            }
        }

        // Finally fall back to the actual typed return
        $returnType = $reflectedMethod->getReturnType();
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
                $types = ['mixed', 'void'];
            }
        }

        return $types;
    }
}
