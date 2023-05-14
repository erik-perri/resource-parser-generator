<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use ResourceParserGenerator\Contracts\Types\TypeContract;

interface ClassPropertyContract
{
    public function type(): TypeContract;
}
