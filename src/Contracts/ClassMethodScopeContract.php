<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ClassMethodScopeContract
{
    public function returnType(): TypeContract;
}
