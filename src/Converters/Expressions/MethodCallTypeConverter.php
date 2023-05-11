<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Converters\Traits\ParsesFetchSides;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use ResourceParserGenerator\Types\NullType;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;

class MethodCallTypeConverter implements TypeConverterContract
{
    use ParsesFetchSides;

    public function __construct(
        private readonly ClassParser $classParser,
        private readonly ExprTypeConverter $exprTypeConverter,
    ) {
        //
    }

    public function convert(MethodCall|NullsafeMethodCall $expr, ResolverContract $resolver): TypeContract
    {
        $leftSide = $this->convertLeftSideToClassScope($expr, $resolver);
        $rightSide = $this->convertRightSide($expr, $resolver);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of method call is not a string');
        }

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(sprintf('Unknown method "%s" in "%s"', $rightSide, $leftSide->name()));
        }


        $return = $methodScope->returnType();

        if ($expr instanceof NullsafeMethodCall) {
            if ($return instanceof UnionType) {
                $return = $return->addToUnion(new NullType());
            } else {
                $return = new UnionType($return, new NullType());
            }
        }

        return $return;
    }

    protected function exprTypeConverter(): ExprTypeConverter
    {
        return $this->exprTypeConverter;
    }

    protected function classParser(): ClassParser
    {
        return $this->classParser;
    }
}
