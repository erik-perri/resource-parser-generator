<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Bool_ as CastBool_;
use PhpParser\Node\Expr\Cast\Int_ as CastInt_;
use PhpParser\Node\Expr\Cast\String_ as CastString_;
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
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Expressions\ArrayExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ArrowFunctionExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\BoolExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ClassConstFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\ConstFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\DoubleExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\MethodCallExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\NumberExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\PropertyFetchExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StaticCallExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\StringExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\TernaryExprTypeConverter;
use ResourceParserGenerator\Converters\Expressions\VariableExprTypeConverter;
use RuntimeException;

/**
 * This class takes a parsed generic PHP expression and attempts to determine what type is returned from the expression.
 *
 * When encountering a chained expression this will attempt to convert the last chunk, recursively calling this for each
 * part of the expression encountered until we find the end type.
 *
 * `$this->resource->method()`
 *  |   |  |      |  |    |
 *  |   |  |      |  MethodCall
 *  |   |  |      |
 *  |   |  PropertyFetch
 *  |   |
 *  Variable
 *
 * ->convert(MethodCall)
 *   ->convert(PropertyFetch from left side of MethodCall)
 *     ->convert(Variable from left side of PropertyFetch)
 *     <-TypeContract of resource class
 *   <-TypeContract of property from resource class
 * <-TypeContract of method return type from property class
 */
class ExpressionTypeConverter implements ExpressionTypeConverterContract
{
    /**
     * @var array<class-string, class-string>
     */
    private readonly array $typeHandlers;

    public function __construct()
    {
        $this->typeHandlers = [
            Array_::class => ArrayExprTypeConverter::class,
            ArrowFunction::class => ArrowFunctionExprTypeConverter::class,
            BooleanAnd::class => BoolExprTypeConverter::class,
            BooleanNot::class => BoolExprTypeConverter::class,
            BooleanOr::class => BoolExprTypeConverter::class,
            CastBool_::class => BoolExprTypeConverter::class,
            CastInt_::class => NumberExprTypeConverter::class,
            CastString_::class => StringExprTypeConverter::class,
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
            Variable::class => VariableExprTypeConverter::class,
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
