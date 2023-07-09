<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ParserData;

interface ParserGeneratorContract
{
    /**
     * @param Collection<int, ParserData> $parsers
     * @param ParserGeneratorContextContract $context
     * @return string
     */
    public function generate(Collection $parsers, ParserGeneratorContextContract $context): string;
}
