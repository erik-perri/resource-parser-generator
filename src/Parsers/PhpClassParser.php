<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassMethod;
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
        $this->parseClassMethods($class, $classScope);

        return $classScope;
    }

    private function parseExtends(Class_ $class, FileScope $scope, PhpFileParser $fileParser): ClassScope|null
    {
        $parent = $class->extends?->toString();
        if (!$parent) {
            return null;
        }

        $resolver = ClassNameResolver::create($scope);
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
        $resolver = ClassNameResolver::create($classScope->file);

        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $classProperty = ClassProperty::create(
                    $prop->name->toString(),
                    $this->declaredTypeParser->parse($property->type, $resolver),
                    $property->flags,
                );

                $classScope->setProperty($classProperty);
            }
        }
    }

    private function parseClassMethods(Class_ $class, ClassScope $classScope): void
    {
        $resolver = ClassNameResolver::create($classScope->file);

        foreach ($class->getMethods() as $methodNode) {
            $parameters = collect();

            foreach ($methodNode->params as $param) {
                $name = $param->var;
                if ($name instanceof Variable) {
                    $name = $name->name;
                    if (!($name instanceof Expr)) {
                        $parameters->put($name, $this->declaredTypeParser->parse($param->type, $resolver));
                    } else {
                        throw new RuntimeException('Unexpected expression in variable name');
                    }
                }
            }

            $method = ClassMethod::create(
                $methodNode->name->toString(),
                $this->declaredTypeParser->parse($methodNode->returnType, $resolver),
                $methodNode->flags,
                $parameters,
            );

            $classScope->setMethod($method);
        }
    }
}
