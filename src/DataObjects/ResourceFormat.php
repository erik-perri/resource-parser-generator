<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

final class ResourceFormat
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param bool $isDefault
     * @param string|null $formatName
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        public readonly bool $isDefault,
        public readonly string|null $formatName,
    ) {
        //
    }
}
