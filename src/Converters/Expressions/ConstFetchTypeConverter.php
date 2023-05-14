<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ConstFetch;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;
use RuntimeException;

class ConstFetchTypeConverter implements TypeConverterContract
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
