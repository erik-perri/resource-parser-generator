<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;

class ResourceConfiguration implements ParserSourceContract
{
    /**
     * @param array{class-string, string} $method
     * @param string|null $parserFile
     * @param string|null $typeName
     * @param string|null $variableName
     */
    public function __construct(
        public readonly array $method,
        public readonly ?string $parserFile = null,
        public readonly ?string $typeName = null,
        public readonly ?string $variableName = null,
    ) {
        //
    }

    public function is(string $className, string $methodName): bool
    {
        return $this->method[0] === $className && $this->method[1] === $methodName;
    }
}
