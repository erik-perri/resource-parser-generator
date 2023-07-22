<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

use Illuminate\Support\Collection;

enum Role: int
{
    case Guest = 0;
    case Admin = 1;

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return collect(config('permissions.' . $this->value, []));
    }
}
