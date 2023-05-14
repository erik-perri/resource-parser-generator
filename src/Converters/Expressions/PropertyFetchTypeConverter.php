<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use ResourceParserGenerator\Contracts\Converters\Expressions\TypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Converters\Traits\ParsesFetchSides;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Types\NullType;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;

class PropertyFetchTypeConverter implements TypeConverterContract
{
    use ParsesFetchSides;

    public function __construct(
        private readonly ClassParser $classParser,
        private readonly ExprTypeConverter $exprTypeConverter,
    ) {
        //
    }

    public function convert(PropertyFetch|NullsafePropertyFetch $expr, ConverterContext $context): TypeContract
    {
        $leftSide = $this->convertLeftSideToClassScope($expr, $context);
        $rightSide = $this->convertRightSide($expr, $context);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of property fetch is not a string');
        }

        $type = $leftSide->propertyType($rightSide);
        if (!$type) {
            throw new RuntimeException(sprintf('Unknown property "%s" in "%s"', $rightSide, $leftSide->name()));
        }

        if ($expr instanceof NullsafePropertyFetch) {
            if ($type instanceof UnionType) {
                $type = $type->addToUnion(new NullType());
            } else {
                $type = new UnionType($type, new NullType());
            }
        }

        return $type;
    }

    protected function exprTypeConverter(): ExprTypeConverter
    {
        return $this->exprTypeConverter;
    }

    protected function classParser(): ClassParser
    {
        return $this->classParser;
    }
}
