<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use ResourceParserGenerator\Parsers\PhpParser\Context\ClassScope;
use ResourceParserGenerator\Parsers\PhpParser\Context\MethodScope;

class MethodScopeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ClassScope $scope,
    ) {
        //
    }

    public static function create(ClassScope $scope): self
    {
        return resolve(self::class, ['scope' => $scope]);
    }

    /**
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof ClassMethod) {
            $this->scope->setMethod(MethodScope::create($this->scope, $node));
        }
    }
}
