<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\Context\MethodScope;
use ResourceParserGenerator\Visitors\FindArrayReturnVisitor;

class MethodScopeArrayReturnTypeExtractor
{
    public function __construct(
        private readonly ExpressionToTypeConverter $expressionObjectTypeParser
    ) {
        //
    }

    /**
     * @return array<array<string, string[]>>
     * @throws ParseResultException
     */
    public function extract(MethodScope $method): array
    {
        $returns = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FindArrayReturnVisitor(
            function (Array_ $array) use ($method, &$returns) {
                $returns[] = $this->extractFromArray($array, $method);
            },
        ));

        $traverser->traverse($method->statements());

        return $returns;
    }

    /**
     * @return array<string, string[]>
     * @throws ParseResultException|ReflectionException
     */
    private function extractFromArray(Array_ $array, MethodScope $method): array
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
            $properties[$key->value] = $this->expressionObjectTypeParser->convert($value, $method);
        }

        return $properties;
    }
}
