<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ClassScopeContract
{
    public function name(): string;

    public function method(string $name): ClassMethodScopeContract|null;

    public function property(string $name): ClassPropertyContract|null;

    public function propertyType(string $name): TypeContract|null;
}
