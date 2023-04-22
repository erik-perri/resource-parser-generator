<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
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

        throw new ParseResultException('Unhandled expression type "' . $expr->getType() . '"', $expr);
    }
}
