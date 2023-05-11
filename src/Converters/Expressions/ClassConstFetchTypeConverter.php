<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;

class ClassConstFetchTypeConverter implements TypeConverterContract
{
    public function __construct(
        private readonly ClassParser $classParser,
        private readonly DeclaredTypeConverter $declaredTypeConverter,
    ) {
        //
    }

    public function convert(ClassConstFetch $expr, ResolverContract $resolver): TypeContract
    {
        $constName = $expr->name;
        if ($constName instanceof Expr) {
            throw new RuntimeException('Class const fetch name is not a string');
        }

        if ($expr->class instanceof Expr) {
            throw new RuntimeException('Class const fetch class is not a string');
        }

        $classType = $this->declaredTypeConverter->convert($expr->class, $resolver);
        if (!($classType instanceof ClassType)) {
            throw new RuntimeException('Class const fetch class is not a class type');
        }

        $classScope = $this->classParser->parse($classType->fullyQualifiedName(), $resolver->resolveThis());
        $constScope = $classScope->constant($constName->name);
        if (!$constScope) {
            throw new RuntimeException(
                sprintf('Unknown constant "%s" in "%s"', $constName->name, $classScope->name()),
            );
        }

        return $constScope->type();
    }
}
