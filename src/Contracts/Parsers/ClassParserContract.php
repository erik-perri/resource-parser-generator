<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Types\ClassType;

interface ClassParserContract
{
    /**
     * @param class-string $className
     * @param class-string|null $staticContext
     * @return ClassScopeContract
     */
    public function parse(string $className, string|null $staticContext = null): ClassScopeContract;

    /**
     * @param ClassType $type
     * @param class-string|null $staticContext
     * @return ClassScopeContract
     */
    public function parseType(ClassType $type, string|null $staticContext = null): ClassScopeContract;
}
