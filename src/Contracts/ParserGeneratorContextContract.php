<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\DataObjects\ParserData;

interface ParserGeneratorContextContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return ParserData|null
     */
    public function find(string $className, string $methodName): ParserData|null;
}
