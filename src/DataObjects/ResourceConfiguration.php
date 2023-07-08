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

    /**
     * TODO Go back to pure array config to avoid this?
     *
     * @param array{
     *     method: array{class-string, string},
     *     parserFile: string,
     *     typeName: string,
     *     variableName: string
     * } $data
     * @return self
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['method'],
            $data['parserFile'],
            $data['typeName'],
            $data['variableName'],
        );
    }
}
