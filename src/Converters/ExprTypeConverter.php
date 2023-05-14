<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Expressions\ArrowFunctionTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ClassConstFetchTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ConstFetchTypeConverter;
use ResourceParserGenerator\Converters\Expressions\DoubleTypeConverter;
use ResourceParserGenerator\Converters\Expressions\MethodCallTypeConverter;
use ResourceParserGenerator\Converters\Expressions\NumberTypeConverter;
use ResourceParserGenerator\Converters\Expressions\PropertyFetchTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StaticCallTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StringTypeConverter;
use ResourceParserGenerator\Converters\Expressions\TernaryTypeConverter;
use RuntimeException;

class ExprTypeConverter
{
    /**
     * @var array<class-string, class-string>
     */
    private readonly array $typeHandlers;

    public function __construct()
    {
        $this->typeHandlers = [
            ArrowFunction::class => ArrowFunctionTypeConverter::class,
            ClassConstFetch::class => ClassConstFetchTypeConverter::class,
            ConstFetch::class => ConstFetchTypeConverter::class,
            DNumber::class => DoubleTypeConverter::class,
            LNumber::class => NumberTypeConverter::class,
            MethodCall::class => MethodCallTypeConverter::class,
            NullsafeMethodCall::class => MethodCallTypeConverter::class,
            NullsafePropertyFetch::class => PropertyFetchTypeConverter::class,
            PropertyFetch::class => PropertyFetchTypeConverter::class,
            StaticCall::class => StaticCallTypeConverter::class,
            String_::class => StringTypeConverter::class,
            Ternary::class => TernaryTypeConverter::class,
            UnaryMinus::class => NumberTypeConverter::class,
            UnaryPlus::class => NumberTypeConverter::class,
            Variable::class => Expressions\VariableTypeConverter::class,
        ];
    }

    public function convert(Expr $expr, ResolverContract $resolver): TypeContract
    {
        $class = get_class($expr);
        $handlerClass = $this->typeHandlers[$class] ?? null;
        if (!$handlerClass) {
            throw new RuntimeException(sprintf('No converter for %s', class_basename($class)));
        }

        /**
         * @var TypeConverterContract $handler
         */
        $handler = resolve($handlerClass);

        return $handler->convert($expr, $resolver);
    }
}
