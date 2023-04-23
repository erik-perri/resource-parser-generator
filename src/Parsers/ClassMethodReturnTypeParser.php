<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\Context\VirtualMethodScope;
use ResourceParserGenerator\Parsers\PhpParser\MethodScopeArrayReturnTypeExtractor;
use RuntimeException;

class ClassMethodReturnTypeParser
{
    public function __construct(
        private readonly FileParser $fileParser,
        private readonly MethodScopeArrayReturnTypeExtractor $arrayReturnTypeLocator,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $classFile
     * @param string $methodName
     * @return string[]|array<string, string[]>
     * @throws ParseResultException|ReflectionException
     */
    public function parse(string $className, string $classFile, string $methodName): array
    {
        $fileScope = $this->fileParser->parse($classFile);

        $classScope = $fileScope->class($className);
        if (!$classScope) {
            throw new RuntimeException('Class "' . $className . '" not found in "' . $classFile . '"');
        }

        $methodScope = $classScope->method($methodName);
        if (!$methodScope) {
            throw new RuntimeException(
                'Method "' . $methodName . '" not found in class "' . $className . '" in "' . $classFile . '"',
            );
        }

        $returnTypes = $methodScope->returnTypes();
        if ($returnTypes === ['array']) {
            if ($methodScope instanceof VirtualMethodScope) {
                return $methodScope->returnTypes();
            }

            $returns = $this->arrayReturnTypeLocator->extract($methodScope);

            return $this->mergeReturnTypes($returns);
        }

        return $returnTypes;
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
        });

        return $merged->all();
    }
}
