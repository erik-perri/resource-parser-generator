<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\Match_;
use PhpParser\Node\MatchArm;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;

class MatchTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
    ) {
        //
    }

    public function convert(Match_ $expr, ConverterContext $context): TypeContract
    {
        if (!count($expr->arms)) {
            throw new RuntimeException('Unhandled armless match expression.');
        }

        $types = collect($expr->arms)
            ->map(function (MatchArm $arm) use ($context) {
                $childContext = ConverterContext::create($context->resolver(), $context->nonNullProperties());

                return $this->expressionTypeConverter->convert($arm->body, $childContext);
            })
            ->unique(fn(TypeContract $type) => $type->describe());

        if ($types->count() > 1) {
            return new UnionType(...$types->all());
        }

        /**
         * @var TypeContract
         */
        return $types->first();
    }
}
