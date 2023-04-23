<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples;

use Illuminate\Http\Request;
use ResourceParserGenerator\Tests\Examples\Models\User;

/**
 * @property User $resource
 * @property $untyped
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

    public function ternaries(Request $request): array
    {
        return [
            'ternary_to_int' => $request->has('something') ? +1 : -1,
            'ternary_to_compound' => $this->resource->created_at ? ($this->resource->updated_at ? true : -1) : 'false',
        ];
    }

    public function scalars(): array
    {
        return [
            'string' => '...',
            'negative_number' => -1,
            'positive_number' => +1,
            'neutral_number' => 1,
            'float' => 1.1,
            'boolean_true' => true,
            'boolean_false' => false,
            'null' => null,
        ];
    }

    public function usingParameter(Request $request): array
    {
        return [
            'path' => $request->path(),
        ];
    }
}
