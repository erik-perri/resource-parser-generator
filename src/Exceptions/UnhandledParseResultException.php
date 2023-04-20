<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Exceptions;

use Exception;
use PhpParser\Node;

class UnhandledParseResultException extends Exception
{
    public function __construct(string $message, public readonly Node $node)
    {
        parent::__construct($message);
    }
}
