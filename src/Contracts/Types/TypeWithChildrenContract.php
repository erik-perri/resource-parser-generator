<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

use Illuminate\Support\Collection;

interface TypeWithChildrenContract
{
    /**
     * @return Collection<int, TypeContract>
     */
    public function children(): Collection;
}
