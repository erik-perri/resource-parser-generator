<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Exceptions;

use Exception;

class ConfigurationParserException extends Exception
{
    /**
     * @param string $message
     * @param string[] $errors
     */
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}
