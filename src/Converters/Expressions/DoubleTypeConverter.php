<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\DNumber;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class DoubleTypeConverter implements TypeConverterContract
{
    public function convert(DNumber $expr, ResolverContract $resolver): TypeContract
    {
        return new Types\FloatType();
    }
}
