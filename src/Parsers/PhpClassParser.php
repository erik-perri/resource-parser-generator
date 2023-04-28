<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Parsers\DataObjects\ClassProperty;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;

class PhpClassParser
{
    public function __construct(
        private readonly DeclaredTypeParser $declaredTypeParser,
    ) {
        //
    }

    public function parse(Class_ $class, FileScope $scope): ClassScope
    {
        $className = $class->name
            ? $class->name->toString()
            : sprintf('AnonymousClass%d', $class->getLine());

        $classScope = ClassScope::create($scope, $className);

        $this->parseClassProperties($class, $classScope);

        $scope->addClass($classScope);

        return $classScope;
    }

    private function parseClassProperties(Class_ $class, ClassScope $classScope): void
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $classProperty = new ClassProperty(
                    $prop->name->toString(),
                    $this->declaredTypeParser->parse($property->type),
                    $property->flags,
                );

                $classScope->addProperty($classProperty);
            }
        }
    }
}
