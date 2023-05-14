<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ClassConstantContract
{
    public function type(): TypeContract;

    public function value(): mixed;
}
