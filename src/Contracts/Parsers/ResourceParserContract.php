<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\DataObjects\ResourceData;

interface ResourceParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceData
     */
    public function parse(string $className, string $methodName): ResourceData;
}
