<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;

interface ResourceParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserCollection|null $result
     * @return ResourceParserCollection
     */
    public function parse(
        string $className,
        string $methodName,
        ResourceParserCollection $result = null
    ): ResourceParserCollection;
}
