<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Scalar\LNumber;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class NumberTypeConverter implements TypeConverterContract
{
    public function convert(UnaryMinus|UnaryPlus|LNumber $expr, ResolverContract $resolver): TypeContract
    {
        return new Types\IntType();
    }
}
