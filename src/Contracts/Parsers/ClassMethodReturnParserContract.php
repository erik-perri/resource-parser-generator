<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ClassMethodReturnParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return TypeContract
     */
    public function parse(string $className, string $methodName): TypeContract;
}
