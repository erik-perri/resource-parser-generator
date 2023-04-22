<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\Context\MethodScope;

class ArrayExpressionTypeExtractor
{
    public function __construct(private readonly ExpressionObjectTypeParser $expressionObjectTypeParser)
    {
        //
    }

    /**
     * @return array<string, string[]>
     * @throws ParseResultException|ReflectionException
     */
    public function extract(Array_ $array, MethodScope $scope): array
    {
        $properties = [];

        foreach ($array->items as $item) {
            if (!$item) {
                throw new ParseResultException('Unexpected null item in resource', $item);
            }

            $key = $item->key;
            if (!($key instanceof String_)) {
                throw new ParseResultException('Unexpected non-string key in resource', $item);
            }

            $value = $item->value;
            $properties[$key->value] = $this->expressionObjectTypeParser->parse($value, $scope);
        }

        return $properties;
    }
}
