<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ResourceData;

interface ResourceParserGeneratorContract
{
    /**
     * @param Collection<int, ResourceData> $parsers
     * @param ResourceGeneratorContextContract $context
     * @return string
     */
    public function generate(Collection $parsers, ResourceGeneratorContextContract $context): string;
}
