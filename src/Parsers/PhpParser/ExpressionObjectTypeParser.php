<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;

class ExpressionObjectTypeParser
{
    public function __construct(
        private readonly ClassFileFinder $classFileFinder,
        private readonly ClassFileTypehintParser $classFileTypehintParser,
    ) {
        //
    }

    public function parse(Expr $expr, ClassTypehints $thisClass): array
    {
        if ($expr instanceof PropertyFetch) {
            $fromClass = null;

            if ($expr->var instanceof PropertyFetch) {
                $from = $this->parse($expr->var, $thisClass);
                if (count($from) !== 1) {
                    throw new ParseResultException(
                        'Unexpected compound left side of property fetch',
                        $expr->var,
                    );
                }


                $fromFile = $this->classFileFinder->find($from[0]);
                $fromClass = $this->classFileTypehintParser->parse($from[0], $fromFile);
            } elseif ($expr->var instanceof Variable) {
                if ($expr->var->name === 'this') {
                    $fromClass = $thisClass;
                }
            }

            if (!$fromClass) {
                throw new ParseResultException(
                    'Unknown type on left side of property fetch',
                    $expr->var,
                );
            }

            if ($expr->name instanceof Identifier) {
                return $fromClass->getPropertyTypes($expr->name->name);
            }

            throw new ParseResultException('Unhandled property in property fetch', $expr);
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
}
