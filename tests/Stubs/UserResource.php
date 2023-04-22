<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Stubs;

use ResourceParserGenerator\Tests\Stubs\Models\User;

/**
 * @property User $resource
 */
class UserResource
{
    public function adminList(): array
    {
        return [
            'id' => $this->resource->getRouteKey(),
            'email' => $this->resource->email,
            'name' => $this->resource->name,
            'created_at' => $this->resource->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->resource->updated_at?->toIso8601ZuluString(),
        ];
    }

    public function authentication(): array
    {
        return [
            'id' => $this->resource->getRouteKey(),
            'email' => $this->resource->email,
            'name' => $this->resource->name,
        ];
    }

    public function combined(): array
    {
        if ($this->resource->created_at) {
            return [
                'email' => $this->resource->email,
                'name' => $this->resource->name,
            ];
        } else {
            return [
                'email' => null,
            ];
        }
    }
}
