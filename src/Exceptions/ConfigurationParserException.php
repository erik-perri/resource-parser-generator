<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Exceptions;

use Exception;

class ConfigurationParserException extends Exception
{
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}
