<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassProperty;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use RuntimeException;

class PhpClassParser
{
    public function __construct(
        private readonly DeclaredTypeParser $declaredTypeParser,
        private readonly ClassFileLocatorContract $classFileLocator,
    ) {
        //
    }

    public function parse(Class_ $class, FileScope $scope, PhpFileParser $fileParser): ClassScope
    {
        $className = $class->name
            ? $class->name->toString()
            : sprintf('AnonymousClass%d', $class->getLine());

        $classScope = ClassScope::create(
            $scope,
            $className,
            $this->parseExtends($class, $scope, $fileParser),
        );

        $this->parseClassProperties($class, $classScope);

        return $classScope;
    }

    private function parseExtends(Class_ $class, FileScope $scope, PhpFileParser $fileParser): ClassScope|null
    {
        $parent = $class->extends?->toString();
        if (!$parent) {
            return null;
        }

        $resolver = ClassNameResolver::make($scope);
        $parentClassName = $resolver->resolve($parent);
        if (!$parentClassName) {
            throw new RuntimeException(sprintf('Could not resolve class "%s"', $parent));
        }

        if (!$this->classFileLocator->exists($parentClassName)) {
            throw new RuntimeException(sprintf('Could not find class file for "%s"', $parentClassName));
        }

        $parentClassFile = $this->classFileLocator->get($parentClassName);
        $parentFileScope = $fileParser->parse(File::get($parentClassFile));
        $parentClassScope = $parentFileScope->classes()->first();

        if (!$parentClassScope) {
            throw new RuntimeException(sprintf('Could not find parent class "%s"', $parentClassName));
        }

        return $parentClassScope;
    }

    private function parseClassProperties(Class_ $class, ClassScope $classScope): void
    {
        $resolver = ClassNameResolver::make($classScope->file);

        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $classProperty = new ClassProperty(
                    $prop->name->toString(),
                    $this->declaredTypeParser->parse($property->type, $resolver),
                    $property->flags,
                );

                $classScope->addProperty($classProperty);
            }
        }
    }
}
