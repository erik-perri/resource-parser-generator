<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PhpParser\Node\Param;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;

interface ParamTypeConverterContract
{
    public function convert(Param $param, ConverterContext $context): TypeContract;
}
