<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ReflectionException;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;

class ExpressionObjectTypeParser
{
    public function __construct(
        private readonly ClassFileFinder $classFileFinder,
        private readonly ClassFileTypehintParser $classFileTypehintParser,
        private readonly ClassMethodReturnParser $classMethodReturnParser,
    ) {
        //
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    public function parse(Expr $expr, ClassTypehints $thisClass): array
    {
        if ($expr instanceof MethodCall) {
            return $this->extractTypeFromMethodCall($expr, $thisClass);
        }

        if ($expr instanceof NullsafeMethodCall) {
            return $this->extractTypeFromNullsafeMethodCall($expr, $thisClass);
        }

        if ($expr instanceof Ternary) {
            return $this->extractTypeFromTernary($expr, $thisClass);
        }

        if ($expr instanceof Variable) {
            if ($expr->name === 'this') {
                return [$thisClass->className];
            }

            throw new ParseResultException('Unhandled variable type', $expr);
        }

        if ($expr instanceof PropertyFetch) {
            return $this->extractTypeFromPropertyFetch($expr, $thisClass);
        }

        if ($expr instanceof ConstFetch) {
            switch ($expr->name->toLowerString()) {
                case 'true':
                case 'false':
                    return ['bool'];
                case 'null':
                    return ['null'];
            }

            throw new ParseResultException('Unhandled constant name "' . $expr->name . '"', $expr);
        }

        if ($expr instanceof UnaryMinus ||
            $expr instanceof UnaryPlus ||
            $expr instanceof LNumber) {
            return ['int'];
        }

        if ($expr instanceof DNumber) {
            return ['float'];
        }

        if ($expr instanceof String_) {
            return ['string'];
        }

        throw new ParseResultException('Unhandled expression type "' . $expr->getType() . '"', $expr);
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromPropertyFetch(PropertyFetch $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classFileTypehintParser->parse($leftSide[0], $leftSideFile);

        return $leftSideClass->getPropertyTypes($rightSide[0]);
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromMethodCall(MethodCall $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classFileTypehintParser->parse($leftSide[0], $leftSideFile);

        return $leftSideClass->getMethodTypes($rightSide[0]);
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromNullsafeMethodCall(NullsafeMethodCall $value, ClassTypehints $resourceClass): array
    {
        [$leftSide, $rightSide] = $this->extractSides($value, $resourceClass);

        $leftSideFile = $this->classFileFinder->find($leftSide[0]);
        $leftSideClass = $this->classMethodReturnParser->parse([$rightSide[0]], $leftSide[0], $leftSideFile);

        $rightSideTypes = $leftSideClass->getMethodTypes($rightSide[0]);

        if ($rightSideTypes === null) {
            throw new ParseResultException(
                'Unknown type "' . $rightSide[0] . '" for right side of property fetch',
                $value->var,
            );
        }

        return array_unique(array_merge($rightSideTypes, ['null']));
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    public function extractSides(
        PropertyFetch|MethodCall|NullsafeMethodCall $value,
        ClassTypehints $resourceClass,
    ): array {
        $leftSide = $this->parse($value->var, $resourceClass);
        if ($value instanceof NullsafeMethodCall) {
            $leftSide = array_filter($leftSide, fn($type) => $type !== 'null');
        }
        if (count($leftSide) !== 1) {
            throw new ParseResultException(
                'Unexpected compound left side of property fetch',
                $value->var,
            );
        }

        $rightSide = $value->name instanceof Expr
            ? $this->parse($value->name, $resourceClass)
            : [$value->name->name];
        if (count($rightSide) !== 1) {
            throw new ParseResultException(
                'Unexpected compound right side of property fetch',
                $value->var,
            );
        }

        return [$leftSide, $rightSide];
    }

    /**
     * @throws ParseResultException|ReflectionException
     */
    private function extractTypeFromTernary(Ternary $value, ClassTypehints $resourceClass): array
    {
        $ifType = $this->parse($value->if, $resourceClass);
        $elseType = $this->parse($value->else, $resourceClass);

        return array_unique(array_merge($ifType, $elseType));
    }
}