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
    public function findGlobal(string $className, string $methodName): ParserData|null;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ParserData|null
     */
    public function findLocal(string $className, string $methodName): ParserData|null;
}
