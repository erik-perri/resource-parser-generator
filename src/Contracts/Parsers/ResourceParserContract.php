<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\DataObjects\ResourceContext;

interface ResourceParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceContext
     */
    public function parse(
        string $className,
        string $methodName,
    ): ResourceContext;
}
