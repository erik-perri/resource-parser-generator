<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Ternary;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Types;

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
