<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ArrowFunction;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\ExprTypeConverter;

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
