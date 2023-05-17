<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Ternary;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;

class TernaryExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(private readonly ExpressionTypeConverterContract $expressionTypeConverter)
    {
        //
    }

    public function convert(Ternary $expr, ConverterContext $context): TypeContract
    {
        $ifType = $this->expressionTypeConverter->convert($expr->if ?? $expr->cond, $context);
        $elseType = $this->expressionTypeConverter->convert($expr->else, $context);

        if (!$expr->if && $ifType instanceof Types\UnionType) {
            $ifType = $ifType->removeNullable();
        }

        if ($ifType->describe() === $elseType->describe()) {
            return $ifType;
        }

        return new Types\UnionType($ifType, $elseType);
    }
}
