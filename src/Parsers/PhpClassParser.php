<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassMethod;
use ResourceParserGenerator\Parsers\DataObjects\ClassProperty;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use RuntimeException;

class PhpClassParser
{
    public function __construct(
        private readonly DeclaredTypeParser $declaredTypeParser,
        private readonly DocBlockParser $docBlockParser,
    ) {
        //
    }

    public function parse(Class_ $class, ClassScope $classScope, ResolverContract $resolver): void
    {
        $classScope->docBlock = $class->getDocComment()
            ? $this->docBlockParser->parse($class->getDocComment()->getText(), $resolver)
            : null;

        $this->parseClassProperties($class, $classScope, $resolver);
        $this->parseClassMethods($class, $classScope, $resolver);
    }

    private function parseClassProperties(Class_ $class, ClassScope $classScope, ResolverContract $resolver): void
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

    private function parseClassMethods(Class_ $class, ClassScope $classScope, ResolverContract $resolver): void
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
