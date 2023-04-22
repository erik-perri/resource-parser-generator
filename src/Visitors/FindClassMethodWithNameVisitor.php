<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class FindClassMethodWithNameVisitor extends NodeVisitorAbstract
{
    /**
     * @param string $methodName
     * @param Closure(ClassMethod): void $handler
     */
    public function __construct(
        private readonly string $methodName,
        private readonly Closure $handler,
    ) {
        //
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function enterNode(Node $node)
    {
        if ($node instanceof ClassMethod) {
            if ($node->name->name === $this->methodName) {
                call_user_func($this->handler, $node);
            }
        }
    }
}
