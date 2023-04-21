<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionUnionType;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\ResolveScope;

class ClassMethodReturnParser
{
    public function __construct(
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
        private readonly ClassFileTypehintParser $classFileTypehintParser,
        private readonly ResolveScope $resolveScope,
    ) {
        //
    }

    /**
     * @param string[] $methodsToCheck
     * @param string $className
     * @param string|null $classFile
     * @return array<string, string|string[]>
     * @throws ReflectionException
     */
    public function parse(array $methodsToCheck, string $className, string|null $classFile): array
    {
        $reflectionClass = new ReflectionClass($className);

        $methods = [];
        $classTypehints = $classFile
            ? $this->classFileTypehintParser->parse($className, $classFile)
            : new ClassTypehints();

        foreach ($reflectionClass->getMethods() as $method) {
            if (!in_array($method->getName(), $methodsToCheck, true)) {
                continue;
            }

            // Check the class typehints first since they were theoretically specified by the user as overrides
            $hintedTypes = $classTypehints->getMethodTypes($method->getName());
            if ($hintedTypes) {
                $methods[$method->getName()] = $hintedTypes;
                continue;
            }

            // Next check the docblock on the actual method
            if ($method->getDocComment()) {
                $docBlock = $this->docBlockFactory->create($method->getDocComment());

                $return = $docBlock->getTagsByName('return');
                $return = count($return) ? reset($return) : null;

                if ($return) {
                    $scope = $classFile
                        ? $this->resolveScope->loadImports($classFile)
                        : null;

                    $methods[$method->getName()] = $this->convertDocblockTagTypes->convert(
                        $return->getType(),
                        $scope,
                    );
                    continue;
                }
            }

            // Finally fall back to the actual typed return
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionUnionType) {
                $types = [];
                foreach ($returnType->getTypes() as $type) {
                    $types[] = $type->getName();
                }
                $methods[$method->getName()] = $types;
            } else {
                if ($returnType) {
                    $methods[$method->getName()] = $returnType->getName();
                } else {
                    $methods[$method->getName()] = ['mixed', 'void'];
                }
            }
        }

        return $methods;
    }
}
