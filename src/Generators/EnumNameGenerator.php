<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Str;
use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;

class EnumNameGenerator implements EnumNameGeneratorContract
{
    public function generateFileName(string $fullyQualifiedName): string
    {
        $shortName = class_basename($fullyQualifiedName);

        return Str::camel($shortName) . '.ts';
    }

    public function generateTypeName(string $fullyQualifiedName): string
    {
        return class_basename($fullyQualifiedName);
    }
}
