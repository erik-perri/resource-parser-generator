<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr\Array_;
use ReflectionException;
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

    /**
     * @param class-string $className
     * @param string $classFile
     * @param string $methodName
     * @return array<string, string[]>
     * @throws ReflectionException
     */
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

    /**
     * @param array<array<string, string[]>> $returns
     * @return array<string, string[]>
     */
    private function mergeReturnTypes(array $returns): array
    {
        /**
         * @var Collection<int, array<string, string[]>> $returns
         */
        $returns = collect($returns);
        $keys = $returns->flatMap(fn(array $properties) => array_keys($properties))->unique();

        /**
         * @var Collection<string, string[]> $merged
         */
        $merged = $keys->mapWithKeys(function (string $key) use ($returns) {
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
