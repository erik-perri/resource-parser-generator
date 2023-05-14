<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use Illuminate\Http\Resources\MissingValue;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Converters\Traits\ParsesFetchSides;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\MixedType;
use ResourceParserGenerator\Types\NullType;
use ResourceParserGenerator\Types\UndefinedType;
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

        $type = $methodScope->returnType();

        if ($rightSide === 'whenLoaded') {
            $type = $this->handleWhenLoaded($expr, $context, $type);
        }

        if ($expr instanceof NullsafeMethodCall) {
            if ($type instanceof UnionType) {
                $type = $type->addToUnion(new NullType());
            } else {
                $type = new UnionType($type, new NullType());
            }
        }

        return $type;
    }

    private function handleWhenLoaded(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        TypeContract $type,
    ): TypeContract {
        if (!($type instanceof UnionType)) {
            throw new RuntimeException(
                sprintf('Unexpected non-union whenLoaded method return, found "%s"', $type->describe()),
            );
        }

        $args = $expr->getArgs();
        if (count($args) < 2) {
            throw new RuntimeException('Unhandled missing second argument for whenLoaded');
        }

        $returnWhenLoaded = $this->exprTypeConverter->convert($args[1]->value, $context);

        $returnWhenUnloaded = count($args) > 2
            ? $this->exprTypeConverter->convert($args[2]->value, $context)
            : new UndefinedType();

        return $type
            ->addToUnion($returnWhenLoaded)
            ->addToUnion($returnWhenUnloaded)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof MixedType)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof ClassType
                && $type->fullyQualifiedName() === MissingValue::class);
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
