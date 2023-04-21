<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use ReflectionException;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Exceptions\UnhandledParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;

class ClassMethodReturnArrayTypeExtractor
{
    public function __construct(
        private readonly ClassFileFinder $classFileFinder,
        private readonly ClassFileTypehintParser $classFileTypehintParser,
        private readonly ClassMethodReturnParser $classMethodReturnParser,
        private readonly ExprObjectTypeParser $exprObjectTypeParser,
    ) {
        //
    }

    /**
     * @throws UnhandledParseResultException|ReflectionException
     */
    public function extract(Array_ $array, ClassTypehints $resourceClass): array
    {
        $properties = [];

        foreach ($array->items as $item) {
            if (!($item->key instanceof String_)) {
                throw new UnhandledParseResultException(
                    'Unexpected return value in resource, not a string key.',
                    $item,
                );
            }

            $value = $item->value;
            $properties[$item->key->value] = match (true) {
                $value instanceof PropertyFetch => $this->extractTypeFromPropertyFetch($value, $resourceClass),
                $value instanceof MethodCall => $this->extractTypeFromMethodCall($value, $resourceClass),
                $value instanceof NullsafeMethodCall => $this->extractTypeFromNullsafeMethodCall(
                    $value,
                    $resourceClass,
                ),
                default => throw new UnhandledParseResultException(
                    'Unexpected array item value type "' . $item->value->getType() . '"',
                    $item->value,
                ),
            };
        }

        return $properties;
    }

    /**
     * @throws UnhandledParseResultException|ReflectionException
     */
    private function extractTypeFromPropertyFetch(PropertyFetch $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classFileTypehintParser->parse($leftSide[0], $leftSideFile);

        return $leftSideClass->getPropertyTypes($rightSide[0]);
    }

    /**
     * @throws UnhandledParseResultException|ReflectionException
     */
    private function extractTypeFromMethodCall(MethodCall $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classFileTypehintParser->parse($leftSide[0], $leftSideFile);

        return $leftSideClass->getMethodTypes($rightSide[0]);
    }

    /**
     * @throws UnhandledParseResultException|ReflectionException
     */
    private function extractTypeFromNullsafeMethodCall(NullsafeMethodCall $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classMethodReturnParser->parse([$rightSide[0]], $leftSide[0], $leftSideFile);

        $rightSideTypes = $leftSideClass->getMethodTypes($rightSide[0]);

        if ($rightSideTypes === null) {
            throw new UnhandledParseResultException(
                'Unexpected right side of property fetch, unable to determine type for "' . $rightSide[0] . '".',
                $value->var,
            );
        }

        return array_unique(array_merge($rightSideTypes, ['null']));
    }

    /**
     * @throws UnhandledParseResultException
     */
    public function extractSides(
        PropertyFetch|MethodCall|NullsafeMethodCall $value,
        ClassTypehints $resourceClass,
    ): array {
        $leftSide = $this->exprObjectTypeParser->parse($value->var, $resourceClass);
        if ($value instanceof NullsafeMethodCall) {
            $leftSide = array_filter($leftSide, fn($type) => $type !== 'null');
        }
        if (count($leftSide) !== 1) {
            throw new UnhandledParseResultException(
                'Unexpected left side of property fetch, not a single type.',
                $value->var,
            );
        }

        $rightSide = $value->name instanceof Expr
            ? $this->exprObjectTypeParser->parse($value->name, $resourceClass)
            : [$value->name->name];
        if (count($rightSide) !== 1) {
            throw new UnhandledParseResultException(
                'Unexpected right side of property fetch, not a single type.',
                $value->var,
            );
        }

        return [$leftSide, $rightSide];
    }
}
