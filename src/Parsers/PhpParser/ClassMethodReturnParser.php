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
     * @param class-string $className
     * @param string $classFile
     * @return ClassTypehints
     * @throws ReflectionException
     */
    public function parse(array|null $methodsToCheck, string $className, string $classFile): ClassTypehints
    {
        $reflectionClass = new ReflectionClass($className);

        $methods = new ClassTypehints($className);

        $classTypehints = $classFile
            ? $this->classFileTypehintParser->parse($className, $classFile)
            : new ClassTypehints($className);

        foreach ($reflectionClass->getMethods() as $method) {
            if ($methodsToCheck !== null && !in_array($method->getName(), $methodsToCheck, true)) {
                continue;
            }

            // Check the class typehints first since they were theoretically specified by the user as overrides
            $hintedTypes = $classTypehints->methodTypes($method->getName());
            if ($hintedTypes) {
                $methods = $methods->addMethod($method->getName(), $hintedTypes);
                continue;
            }

            // Next check the docblock on the actual method
            if ($method->getDocComment()) {
                $docBlock = $this->docBlockFactory->create($method->getDocComment());

                $returns = $docBlock->getTagsByName('return');
                $return = count($returns) ? $returns[0] : null;

                if ($return) {
                    $scope = $this->resolveScope->loadImports($classFile);

                    $type = $return instanceof TagWithType
                        ? $return->getType()
                        : new Compound([new Mixed_(), new Void_()]);

                    $methods = $methods->addMethod(
                        $method->getName(),
                        $this->convertDocblockTagTypes->convert($type, $scope),
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
                $methods = $methods->addMethod($method->getName(), $types);
            } else {
                if ($returnType) {
                    // @phpstan-ignore-next-line -- ReflectionType::getName() is not missing
                    $types = [$returnType->getName()];
                } else {
                    $types = ['mixed', 'void'];
                }

                $methods = $methods->addMethod($method->getName(), $types);
            }
        }

        return $methods;
    }
}
