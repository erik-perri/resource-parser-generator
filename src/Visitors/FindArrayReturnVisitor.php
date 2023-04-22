<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use ResourceParserGenerator\Exceptions\ParseResultException;

class FindArrayReturnVisitor extends NodeVisitorAbstract
{
    /**
     * @param Closure(Array_): void $handler
     */
    public function __construct(private readonly Closure $handler)
    {
        //
    }

    /**
     * @throws ParseResultException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Return_) {
            $expression = $node->expr;
            if (!($expression instanceof Array_)) {
                throw new ParseResultException('Unexpected non-array return value', $node->expr);
            }

            call_user_func($this->handler, $expression);
        }
    }
}
