<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\ConstFetch;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class ConstFetchTypeConverter implements TypeConverterContract
{
    public function convert(ConstFetch $expr, ResolverContract $resolver): TypeContract
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
