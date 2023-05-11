<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Ternary;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class TernaryTypeConverter implements TypeConverterContract
{
    public function __construct(private readonly ExprTypeConverter $exprTypeConverter)
    {
        //
    }

    public function convert(Ternary $expr, ResolverContract $resolver): TypeContract
    {
        $ifType = $this->exprTypeConverter->convert($expr->if ?? $expr->cond, $resolver);
        $elseType = $this->exprTypeConverter->convert($expr->else, $resolver);

        if ($ifType->describe() === $elseType->describe()) {
            return $ifType;
        }

        return new Types\UnionType($ifType, $elseType);
    }
}
