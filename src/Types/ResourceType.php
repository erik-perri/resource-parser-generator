<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;

class ResourceType implements TypeContract
{
    public function __construct(public readonly bool $isClosed = false)
    {
        //
    }

    public function describe(): string
    {
        return 'resource';
    }
}
