<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

interface EnumNameGeneratorContract
{
    public function generateFileName(string $fullyQualifiedName): string;

    public function generateTypeName(string $fullyQualifiedName): string;
}
