<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Ternary;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Types;

class TernaryTypeConverter implements TypeConverterContract
{
    public function __construct(private readonly ExprTypeConverter $exprTypeConverter)
    {
        //
    }

    public function convert(Ternary $expr, ConverterContext $context): TypeContract
    {
        $ifType = $this->exprTypeConverter->convert($expr->if ?? $expr->cond, $context);
        $elseType = $this->exprTypeConverter->convert($expr->else, $context);

        if (!$expr->if && $ifType instanceof Types\UnionType) {
            $ifType = $ifType->removeNullable();
        }

        if ($ifType->describe() === $elseType->describe()) {
            return $ifType;
        }

        return new Types\UnionType($ifType, $elseType);
    }
}
