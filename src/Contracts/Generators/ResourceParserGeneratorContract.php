<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceContext;

interface ResourceParserGeneratorContract
{
    /**
     * @param Collection<int, ResourceContext> $parsers
     * @return string
     */
    public function generate(Collection $parsers): string;
}
