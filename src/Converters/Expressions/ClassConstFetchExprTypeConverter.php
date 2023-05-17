<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types\ClassType;
use RuntimeException;

class ClassConstFetchExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ClassParserContract $classParser,
        private readonly DeclaredTypeConverterContract $declaredTypeConverter,
    ) {
        //
    }

    public function convert(ClassConstFetch $expr, ConverterContext $context): TypeContract
    {
        $constName = $expr->name;
        if ($constName instanceof Expr) {
            throw new RuntimeException('Class const fetch name is not a string');
        }

        if ($expr->class instanceof Expr) {
            throw new RuntimeException('Class const fetch class is not a string');
        }

        $classType = $this->declaredTypeConverter->convert($expr->class, $context->resolver());
        if (!($classType instanceof ClassType)) {
            throw new RuntimeException('Class const fetch class is not a class type');
        }

        $classScope = $this->classParser->parse($classType->fullyQualifiedName(), $context->resolver()->resolveThis());
        $constScope = $classScope->constant($constName->name);
        if (!$constScope) {
            throw new RuntimeException(
                sprintf('Unknown constant "%s" in "%s"', $constName->name, $classScope->name()),
            );
        }

        return $constScope->type();
    }
}
