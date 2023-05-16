<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use ReflectionType;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ReflectionTypeConverterContract
{
    public function convert(ReflectionType|null $type): TypeContract;
}
