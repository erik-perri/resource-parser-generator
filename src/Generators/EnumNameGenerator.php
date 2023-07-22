<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;

class EnumNameGenerator implements EnumNameGeneratorContract
{
    public function generateFileName(string $fullyQualifiedName): string
    {
        return class_basename($fullyQualifiedName) . '.ts';
    }

    public function generateTypeName(string $fullyQualifiedName): string
    {
        return class_basename($fullyQualifiedName);
    }
}
