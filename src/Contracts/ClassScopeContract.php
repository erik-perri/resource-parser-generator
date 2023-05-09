<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\Types\Contracts\TypeContract;

interface ClassScopeContract
{
    public function constant(string $name): ClassConstantContract|null;

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string;

    public function method(string $name): ClassMethodScopeContract|null;

    public function name(): string;

    public function property(string $name): ClassPropertyContract|null;

    public function propertyType(string $name): TypeContract|null;
}
