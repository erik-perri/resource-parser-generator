<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\Expr\Array_;
use PhpParser\NodeTraverser;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\Context\MethodScope;
use ResourceParserGenerator\Visitors\FindArrayReturnVisitor;

class MethodScopeArrayReturnTypeLocator
{
    public function __construct(private readonly ArrayExpressionTypeExtractor $typeExtractor)
    {
        //
    }

    /**
     * @return array<array<string, string[]>>
     * @throws ParseResultException
     */
    public function locate(MethodScope $method): array
    {
        $returns = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FindArrayReturnVisitor(
            function (Array_ $array) use ($method, &$returns) {
                $returns[] = $this->typeExtractor->extract($array, $method);
            },
        ));

        $traverser->traverse($method->statements());

        return $returns;
    }
}
