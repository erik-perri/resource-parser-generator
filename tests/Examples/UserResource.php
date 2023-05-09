<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples;

use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Phar;
use ResourceParserGenerator\Tests\Examples\Models\User;

/**
 * @property User $resource
 * @property $untyped
 */
class UserResource extends JsonResource
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

    public function ternaries(Request $request): array
    {
        return [
            'ternary_to_int' => $request->has('something') ? +1 : -1,
            'ternary_to_compound' => $this->resource->created_at ? ($this->resource->updated_at ? true : -1) : 'false',
        ];
    }

    public function usingParameter(Request $request): array
    {
        return [
            'path' => $request->headers->get('path'),
        ];
    }

    public function usingWhenLoaded(): array
    {
        return [
            'related' => $this->whenLoaded('related', fn() => $this->resource->related->name),
        ];
    }

    public function usingWhenLoadedFallback(): array
    {
        return [
            'related' => $this->whenLoaded('related', fn() => $this->resource->related->name, 'none'),
        ];
    }

    public function staticCallOrConst(): array
    {
        return [
            'const_float' => User::CONST_FLOAT,
            'const_string' => User::CONST_STRING,
            'explicit_method' => User::getExplicitStaticValue(),
            'hinted_method' => User::getHintedStaticValue(),
            'reflected_const' => DateTimeZone::AMERICA,
            'reflected_method' => Phar::getSupportedCompression(),
        ];
    }

    public function relatedResource(): array
    {
        return [
            'with_format_default' => RelatedResource::make($this->resource->related),
            'with_format_short' => RelatedResource::make($this->resource->related)
                ->format('short'),
            'with_format_verbose' => RelatedResource::make($this->resource->related)
                ->format(RelatedResource::FORMAT_VERBOSE),
        ];
    }
}
