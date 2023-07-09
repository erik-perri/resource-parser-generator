<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class EnumType implements TypeContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param TypeContract $backingType
     */
    public function __construct(
        public readonly string $fullyQualifiedName,
        public readonly TypeContract $backingType
    ) {
        //
    }

    public function describe(): string
    {
        return sprintf('enum<%s, %s>', $this->fullyQualifiedName, $this->backingType->describe());
    }
}
