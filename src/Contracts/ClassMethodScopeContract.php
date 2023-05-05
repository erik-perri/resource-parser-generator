<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\Types\Contracts\TypeContract;

interface ClassMethodScopeContract
{
    public function returnType(): TypeContract;
}
