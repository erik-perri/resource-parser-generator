<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Exceptions;

use Exception;
use PhpParser\Node;

class ParseResultException extends Exception
{
    public function __construct(string $messagePrefix, public readonly Node $node)
    {
        $message = $node->getLine() === -1
            ? "$messagePrefix."
            : "$messagePrefix at line {$node->getLine()}.";

        parent::__construct($message);
    }
}
