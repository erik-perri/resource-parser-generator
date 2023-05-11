<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ArrowFunction;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ArrowFunctionTypeConverter implements TypeConverterContract
{
    public function __construct(private readonly ExprTypeConverter $exprTypeConverter)
    {
        //
    }

    public function convert(ArrowFunction $expr, ResolverContract $resolver): TypeContract
    {
        return $this->exprTypeConverter->convert($expr->expr, $resolver);
    }
}
