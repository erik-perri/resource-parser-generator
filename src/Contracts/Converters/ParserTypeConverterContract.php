<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ParserTypeConverterContract
{
    public function convert(TypeContract $type): ParserTypeContract;
}
