<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use ResourceParserGenerator\Contracts\Types\TypeContract;

interface VariableTypeConverterContract
{
    public function convert(mixed $variable): TypeContract;
}
