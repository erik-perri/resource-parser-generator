<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Illuminate\Support\Collection;

interface ImportCollectionContract
{
    /**
     * @return Collection<int, ImportContract>
     */
    public function imports(): Collection;
}
