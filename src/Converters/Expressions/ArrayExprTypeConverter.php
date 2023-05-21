<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\ExpressionContextProcessor;
use ResourceParserGenerator\Types\ArrayType;
use ResourceParserGenerator\Types\ArrayWithPropertiesType;
use RuntimeException;

class ArrayExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly ExpressionContextProcessor $expressionContextProcessor,
    ) {
        //
    }

    public function convert(Array_ $expr, ConverterContext $context): TypeContract
    {
        $items = collect($expr->items)->filter();
        if ($items->isEmpty()) {
            return new ArrayType(null, null);
        }

        return new ArrayWithPropertiesType(
            $items->mapWithKeys(function (ArrayItem $item) use ($context) {
                $key = $item->key;
                if (!($key instanceof String_)) {
                    throw new RuntimeException('Unexpected non-string key in resource');
                }

                $context = ConverterContext::create($context->resolver(), $context->nonNullProperties());
                $type = $this->expressionTypeConverter->convert($item->value, $context);

                return [$key->value => $this->expressionContextProcessor->process($type, $context)];
            }),
        );
    }
}