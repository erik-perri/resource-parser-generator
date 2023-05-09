<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Types\Contracts\TypeContract;

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

    /**
     * @return class-string
     */
    public function describe(): string
    {
        return $this->fullyQualifiedName;
    }

    public function alias(): string|null
    {
        return $this->alias;
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }
}
