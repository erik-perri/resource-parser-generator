<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\EnumCaseData;

interface EnumCaseParserContract
{
    /**
     * @param class-string $className
     * @return Collection<int, EnumCaseData>
     */
    public function parse(string $className): Collection;
}
