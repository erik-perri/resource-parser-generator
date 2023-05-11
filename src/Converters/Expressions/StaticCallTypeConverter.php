<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;

class StaticCallTypeConverter implements TypeConverterContract
{
    public function __construct(
        private readonly ClassParser $classParser,
        private readonly DeclaredTypeConverter $declaredTypeConverter,
    ) {
        //
    }

    public function convert(StaticCall $expr, ResolverContract $resolver): TypeContract
    {
        $methodName = $expr->name;
        if ($methodName instanceof Expr) {
            throw new RuntimeException('Static call name is not a string');
        }

        if ($expr->class instanceof Expr) {
            throw new RuntimeException('Static call class is not a string');
        }

        $classType = $this->declaredTypeConverter->convert($expr->class, $resolver);
        if (!($classType instanceof ClassType)) {
            throw new RuntimeException('Static call class is not a class type');
        }

        $classScope = $this->classParser->parse($classType->fullyQualifiedName());
        $methodScope = $classScope->method($methodName->name);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" in "%s"', $methodName->name, $classScope->name()),
            );
        }

        return $methodScope->returnType();
    }
}
