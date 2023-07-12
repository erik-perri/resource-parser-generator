<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ParserData;

interface ParserGeneratorContract
{
    /**
     * @param Collection<int, ParserData> $localParsers
     * @param Collection<int, ParserData> $globalParsers
     * @return string
     */
    public function generate(Collection $localParsers, Collection $globalParsers): string;
}
