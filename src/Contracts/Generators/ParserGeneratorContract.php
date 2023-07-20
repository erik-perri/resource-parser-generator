<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ParserData;

interface ParserGeneratorContract
{
    /**
     * @param ParserData $parser
     * @param Collection<int, ParserData> $otherParsers
     * @return string
     */
    public function generate(ParserData $parser, Collection $otherParsers): string;
}
