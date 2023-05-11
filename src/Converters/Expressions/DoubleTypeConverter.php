<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\DNumber;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class DoubleTypeConverter implements TypeConverterContract
{
    public function convert(DNumber $expr, ResolverContract $resolver): TypeContract
    {
        return new Types\FloatType();
    }
}
