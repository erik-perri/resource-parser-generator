<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ArrowFunction;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\ExpressionContextProcessor;

class ArrowFunctionExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly ExpressionContextProcessor $expressionContextProcessor,
    ) {
        //
    }

    public function convert(ArrowFunction $expr, ConverterContext $context): TypeContract
    {
        $childContext = ConverterContext::create($context->resolver(), $context->nonNullProperties());
        $type = $this->expressionTypeConverter->convert($expr->expr, $childContext);

        return $this->expressionContextProcessor->process($type, $childContext);
    }
}
