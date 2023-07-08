<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PhpParser\Node\Param;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ParamTypeConverterContract
{
    public function convert(Param $param, ConverterContext $context): TypeContract;
}
