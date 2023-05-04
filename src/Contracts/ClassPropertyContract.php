<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

interface ClassPropertyContract
{
    public function type(): TypeContract;
}
