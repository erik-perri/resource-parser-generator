<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr\Array_;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;

class ClassMethodReturnArrayTypeParser
{
    public function __construct(
        private readonly ClassFileTypehintParser $classFileTypehintParser,
        private readonly ClassMethodReturnArrayTypeLocator $returnArrayTypeLocator,
        private readonly ClassMethodReturnArrayTypeExtractor $returnArrayTypeExtractor,
    ) {
        //
    }

    public function parse(string $className, string $classFile, string $methodName): array
    {
        $returns = [];
        $typehints = $this->classFileTypehintParser->parse($className, $classFile);

        $this->returnArrayTypeLocator->locate(
            $classFile,
            $methodName,
            function (Array_ $array) use (&$returns, $typehints) {
                $returns[] = $this->returnArrayTypeExtractor->extract($array, $typehints);
            },
        );

        return $this->mergeReturnTypes($returns);
    }

    private function mergeReturnTypes(array $returns): array
    {
        $returns = collect($returns);
        $keys = $returns->flatMap(fn(array $properties) => array_keys($properties))->unique();

        return $keys->mapWithKeys(function ($key) use ($returns) {
            $values = $returns->map(fn(array $properties) => $properties[$key] ?? ['undefined'])
                ->flatten()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return [$key => $values];
        })->toArray();
    }
}
