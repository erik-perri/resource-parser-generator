<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
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
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\Expressions\ArrowFunctionExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ClassConstFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ConstFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\DoubleExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\MethodCallExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\NumberExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\PropertyFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StaticCallExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StringExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\TernaryExprTypeConverter;
use RuntimeException;

class ExpressionTypeConverter implements ExpressionTypeConverterContract
{
    /**
     * @var array<class-string, class-string>
     */
    private readonly array $typeHandlers;

    public function __construct()
    {
        $this->typeHandlers = [
            Array_::class => Expressions\ArrayExprTypeConverter::class,
            ArrowFunction::class => ArrowFunctionExprTypeConverter::class,
            ClassConstFetch::class => ClassConstFetchExprTypeConverter::class,
            ConstFetch::class => ConstFetchExprTypeConverter::class,
            DNumber::class => DoubleExprTypeConverter::class,
            LNumber::class => NumberExprTypeConverter::class,
            MethodCall::class => MethodCallExprTypeConverter::class,
            NullsafeMethodCall::class => MethodCallExprTypeConverter::class,
            NullsafePropertyFetch::class => PropertyFetchExprTypeConverter::class,
            PropertyFetch::class => PropertyFetchExprTypeConverter::class,
            StaticCall::class => StaticCallExprTypeConverter::class,
            String_::class => StringExprTypeConverter::class,
            Ternary::class => TernaryExprTypeConverter::class,
            UnaryMinus::class => NumberExprTypeConverter::class,
            UnaryPlus::class => NumberExprTypeConverter::class,
            Variable::class => Expressions\VariableExprTypeConverter::class,
        ];
    }

    public function convert(Expr $expr, ConverterContext $context): TypeContract
    {
        $class = get_class($expr);
        $handlerClass = $this->typeHandlers[$class] ?? null;
        if (!$handlerClass) {
            throw new RuntimeException(sprintf('No converter for %s', class_basename($class)));
        }

        /**
         * @var ExpressionTypeConverterContract $handler
         */
        $handler = resolve($handlerClass);

        return $handler->convert($expr, $context);
    }
}
