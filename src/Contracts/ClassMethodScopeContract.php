<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\Types\Contracts\TypeContract;

interface ClassMethodScopeContract
{
    public function attribute(string $className): AttributeContract|null;

    public function returnType(): TypeContract;
}
