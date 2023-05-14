<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators\Contracts;

interface ParserNameGeneratorContract
{
    public function generateFileName(string $fullyQualifiedName): string;

    public function generateTypeName(string $fullyQualifiedName, string $methodName): string;

    public function generateVariableName(string $fullyQualifiedName, string $methodName): string;
}
