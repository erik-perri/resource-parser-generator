<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use ResourceParserGenerator\Exceptions\UnhandledParseResultException;

class FindArrayReturnVisitor extends NodeVisitorAbstract
{
    /**
     * @param Closure(Array_): void $handler
     */
    public function __construct(
        private readonly Closure $handler,
    ) {
        //
    }

    /**
     * @throws UnhandledParseResultException
     */
    public function leaveNode(Node $node): void
    {
        if (!($node instanceof Return_)) {
            return;
        }

        if (!($node->expr instanceof Array_)) {
            throw new UnhandledParseResultException(
                'Unexpected return value in resource, not array.',
                $node->expr,
            );
        }

        call_user_func($this->handler, $node->expr);
    }
}
