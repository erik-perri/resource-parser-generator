<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

use Illuminate\Support\Collection;

enum Role: string
{
    case Admin = 'admin';
    case Guest = 'guest';

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return collect(config('permissions.' . $this->value, []));
    }
}
