<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;
use Throwable;

class ErrorType implements TypeContract
{
    public function __construct(public readonly Throwable $exception)
    {
        //
    }

    public function describe(): string
    {
        return 'error';
    }
}
