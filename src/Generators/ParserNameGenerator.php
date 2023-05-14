<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Str;
use ResourceParserGenerator\Generators\Contracts\ParserNameGeneratorContract;

class ParserNameGenerator implements ParserNameGeneratorContract
{
    public function generateVariableName(string $fullyQualifiedName, string $methodName): string
    {
        $shortName = class_basename($fullyQualifiedName);

        return Str::camel($shortName) . Str::studly($methodName) . 'Parser';
    }

    public function generateTypeName(string $fullyQualifiedName, string $methodName): string
    {
        $shortName = class_basename($fullyQualifiedName);

        return $shortName . Str::studly($methodName);
    }
}
