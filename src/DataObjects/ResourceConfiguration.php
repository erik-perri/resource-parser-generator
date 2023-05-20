<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ResourceConfiguration
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param string|null $outputType
     * @param string|null $outputVariable
     * @param string|null $outputFilePath
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        public readonly string|null $outputType,
        public readonly string|null $outputVariable,
        public readonly string|null $outputFilePath,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return bool
     */
    public function is(string $className, string $methodName): bool
    {
        return $this->className === $className && $this->methodName === $methodName;
    }
}
