<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Contracts;

interface TypeContract
{
    public function describe(): string;
}
