<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

enum Permission: string
{
    case Read = 'read';
    case Write = 'write';
    case Delete = 'delete';
}
