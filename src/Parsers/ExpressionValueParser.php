<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use RuntimeException;

class ExpressionValueParser implements ExpressionValueParserContract
{
    public function __construct(
        private readonly ClassConstFetchValueParser $classConstFetchValueParser,
    ) {
        //
    }

    public function parse(Expr $expr, ResolverContract $resolver): mixed
    {
        if ($expr instanceof String_ ||
            $expr instanceof LNumber ||
            $expr instanceof DNumber) {
            return $expr->value;
        }

        if ($expr instanceof ClassConstFetch) {
            return $this->classConstFetchValueParser->parse($expr, $resolver);
        }

        throw new RuntimeException(sprintf('Unhandled value type "%s"', get_class($expr)));
    }
}
