<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceData;

interface ResourceMethodParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param Collection<int, ResourceData> $parsedResources
     * @return Collection<int, ResourceData>
     */
    public function parse(string $className, string $methodName, Collection $parsedResources): Collection;
}
