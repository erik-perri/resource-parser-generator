<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class StringTypeConverter implements TypeConverterContract
{
    public function convert(String_ $expr, ResolverContract $resolver): TypeContract
    {
        return new Types\StringType();
    }
}
