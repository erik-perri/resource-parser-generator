<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\EnumCaseData;

interface EnumScopeContract
{
    /**
     * @return Collection<int, EnumCaseData>
     */
    public function cases(): Collection;

    /**
     * @return class-string
     */
    public function fullyQualifiedName(): string;

    public function name(): string;
}
