<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class StringTypeConverter implements TypeConverterContract
{
    public function convert(String_ $expr, ResolverContract $resolver): TypeContract
    {
        return new Types\StringType();
    }
}
