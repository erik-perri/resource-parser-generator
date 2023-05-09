<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface AttributeContract
{
    public function argument(int $index): mixed;
}
