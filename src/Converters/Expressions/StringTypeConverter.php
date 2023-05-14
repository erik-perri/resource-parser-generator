<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;

class StringTypeConverter implements TypeConverterContract
{
    public function convert(String_ $expr, ConverterContext $context): TypeContract
    {
        return new Types\StringType();
    }
}
