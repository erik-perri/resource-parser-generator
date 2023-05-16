<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use ResourceParserGenerator\Contracts\Parsers\ClassConstFetchValueParserContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use RuntimeException;

class ClassConstFetchValueParser implements ClassConstFetchValueParserContract
{
    public function __construct(
        private readonly ClassParserContract $classParser,
    ) {
        //
    }

    public function parse(ClassConstFetch $value, ResolverContract $resolver): mixed
    {
        if ($value->class instanceof Expr) {
            throw new RuntimeException('Class const fetch class is not a string');
        }

        $referencedClassName = $value->class->toString();

        if ($referencedClassName === 'self') {
            $resolvedClassName = $resolver->resolveThis();
        } else {
            $resolvedClassName = $resolver->resolveClass($referencedClassName);
        }

        if (!$resolvedClassName) {
            throw new RuntimeException(
                sprintf('Unknown class "%s" for class const fetch', $value->class->toString()),
            );
        }

        $fetchClass = $this->classParser->parse($resolvedClassName);

        if ($value->name instanceof Expr\Error) {
            throw new RuntimeException('Class const fetch name is not a string');
        }

        $constName = $value->name->toString();
        $constScope = $fetchClass->constant($constName);

        if (!$constScope) {
            throw new RuntimeException(
                sprintf('Unknown constant "%s" for class "%s"', $constName, $fetchClass->fullyQualifiedName()),
            );
        }

        return $constScope->value();
    }
}
