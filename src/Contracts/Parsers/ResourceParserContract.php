<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;

interface ResourceParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserContextCollection|null $parsed
     * @return ResourceParserContextCollection
     */
    public function parse(
        string $className,
        string $methodName,
        ResourceParserContextCollection $parsed = null,
    ): ResourceParserContextCollection;
}
