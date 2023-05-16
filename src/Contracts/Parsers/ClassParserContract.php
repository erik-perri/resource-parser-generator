<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Contracts\ClassScopeContract;

interface ClassParserContract
{
    /**
     * @param class-string $className
     * @param class-string|null $staticContext
     * @return ClassScopeContract
     */
    public function parse(string $className, string|null $staticContext = null): ClassScopeContract;
}
