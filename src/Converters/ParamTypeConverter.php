<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PhpParser\Node\Name;
use PhpParser\Node\Param;
use ResourceParserGenerator\Contracts\Converters\ParamTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use RuntimeException;

class ParamTypeConverter implements ParamTypeConverterContract
{
    public function __construct(private readonly DeclaredTypeConverter $declaredTypeConverter)
    {
        //
    }

    public function convert(Param $param, ConverterContext $context): TypeContract
    {
        if ($param->type instanceof Name) {
            return $this->declaredTypeConverter->convert($param->type, $context->resolver());
        }

        if ($param->type === null) {
            throw new RuntimeException('Unhandled untyped param');
        }

        throw new RuntimeException(sprintf('Unhandled param type "%s"', get_class($param->type)));
    }
}
