<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ConstFetch;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class ConstFetchExprTypeConverter implements ExprTypeConverterContract
{
    public function convert(ConstFetch $expr, ConverterContext $context): TypeContract
    {
        switch ($expr->name->toLowerString()) {
            case 'true':
            case 'false':
                return new Types\BoolType();
            case 'null':
                return new Types\NullType();
        }

        throw new RuntimeException(sprintf('Unhandled constant name "%s"', $expr->name));
    }
}
