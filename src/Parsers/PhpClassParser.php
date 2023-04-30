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
use ResourceParserGenerator\Resolvers\Resolver;
use ResourceParserGenerator\Types;
use RuntimeException;

class PhpClassParser
{
    public function __construct(
        private readonly DeclaredTypeParser $declaredTypeParser,
        private readonly ClassFileLocatorContract $classFileLocator,
        private readonly DocBlockParser $docBlockParser,
    ) {
        //
    }

    public function parse(Class_ $class, FileScope $scope, PhpFileParser $fileParser): ClassScope
    {
        $className = $class->name
            ? $class->name->toString()
            : sprintf('AnonymousClass%d', $class->getLine());

        $classResolver = ClassNameResolver::create($scope);
        $fullyQualifiedClassName = $classResolver->resolve($className);
        if (!$fullyQualifiedClassName) {
            throw new RuntimeException(sprintf('Could not resolve class "%s"', $className));
        }

        $classType = new Types\ClassType($fullyQualifiedClassName, $className);
        $resolver = Resolver::create($classResolver, $classType->name());

        $classScope = ClassScope::create(
            $scope,
            $className,
            $this->parseExtends($class, $fileParser, $resolver),
            $class->getDocComment()
                ? $this->docBlockParser->parse($class->getDocComment()->getText(), $resolver)
                : null,
        );

        $this->parseClassProperties($class, $classScope, $resolver);
        $this->parseClassMethods($class, $classScope, $resolver);

        return $classScope;
    }

    private function parseExtends(
        Class_ $class,
        PhpFileParser $fileParser,
        Resolver $resolver
    ): ClassScope|null {
        $parent = $class->extends?->toString();
        if (!$parent) {
            return null;
        }

        $parentClassName = $resolver->resolveClass($parent);
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

    private function parseClassProperties(Class_ $class, ClassScope $classScope, Resolver $resolver): void
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $classProperty = ClassProperty::create(
                    $prop->name->toString(),
                    $this->declaredTypeParser->parse($property->type, $resolver),
                    $property->flags,
                    $property->getDocComment()
                        ? $this->docBlockParser->parse($property->getDocComment()->getText(), $resolver)
                        : null,
                );

                $classScope->setProperty($classProperty);
            }
        }
    }

    private function parseClassMethods(Class_ $class, ClassScope $classScope, Resolver $resolver): void
    {
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
                $methodNode->getDocComment()
                    ? $this->docBlockParser->parse($methodNode->getDocComment()->getText(), $resolver)
                    : null,
            );

            $classScope->setMethod($method);
        }
    }
}
