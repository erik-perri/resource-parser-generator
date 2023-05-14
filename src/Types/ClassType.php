<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use RuntimeException;

class ClassType implements TypeContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param string|null $alias
     */
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly string|null $alias,
    ) {
        //
    }

    public function alias(): string|null
    {
        return $this->alias;
    }

    /**
     * @return class-string
     */
    public function describe(): string
    {
        return $this->fullyQualifiedName;
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    public function parserType(): ParserTypeContract
    {
        throw new RuntimeException(class_basename(self::class) . ' cannot be converted to parser type.');
    }
}
