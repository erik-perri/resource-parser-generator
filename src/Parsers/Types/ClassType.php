<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Types;

use ResourceParserGenerator\Contracts\TypeContract;

class ClassType implements TypeContract
{
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly string|null $alias,
    ) {
        //
    }

    public function name(): string
    {
        return $this->fullyQualifiedName;
    }

    public function alias(): string|null
    {
        return $this->alias;
    }
}
