<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Resources;

use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Phar;
use ResourceParserGenerator\Tests\Examples\Enums\Permission;
use ResourceParserGenerator\Tests\Examples\Enums\Role;
use ResourceParserGenerator\Tests\Examples\Models\User;
use ResourceParserGenerator\Tests\Examples\Resources\Nested\RelatedResource;

/**
 * @property User $resource
 * @property $untyped
 */
class UserResource extends JsonResource
{
    public function base(): array
    {
        return [
            'id' => $this->resource->getRouteKey(),
            'email' => $this->resource->email,
            'created_at' => $this->resource->created_at?->toIso8601ZuluString(),
        ];
    }

    public function childArrays(): array
    {
        return [
            'should_have_been_a_resource' => $this->whenLoaded(
                'related',
                [
                    'id' => $this->resource->related->getRouteKey(),
                    'should_have_been_when_loaded' => $this->resource->latestPost
                        ? PostResource::make($this->resource->latestPost)
                        : null,
                ],
                [],
            ),
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

    public function enumWithoutValue(): array
    {
        return [
            'latestStatus' => $this->whenLoaded('latestPost', fn() => $this->resource->latestPost->status),
            'legacyStatus' => $this->whenLoaded('latestPost', fn() => $this->resource->latestPost->legacyStatus),
        ];
    }

    public function enumMethods(): array
    {
        return [
            'permissions' => $this->resource->role->permissions()->map(
                fn(Permission $permission) => $permission->value,
            ),
        ];
    }

    public function variableHinted(): array
    {
        /** @var string $variable Should be nullable, but not due to override */
        $variable = $this->resource->created_at?->toIso8601ZuluString();

        return [
            'variable' => $variable,
        ];
    }

    public function variableUnion(): array
    {
        if ($this->resource->created_at) {
            $variable = 'string';
        } else {
            $variable = 1;
        }

        return [
            'variable' => $variable,
        ];
    }

    public function matchedValue(): array
    {
        /** @noinspection PhpUnusedMatchConditionInspection PhpDuplicateMatchArmBodyInspection */
        return [
            'matched_value' => match ($this->resource->role) {
                Role::Admin => PostResource::make($this->resource->latestPost)->format(PostResource::BASE),
                Role::Guest => PostResource::make($this->resource->latestPost)->format(PostResource::SIMPLE),
                default => PostResource::make($this->resource->latestPost)->format(PostResource::SIMPLE),
            },
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

    public function ternaries(Request $request): array
    {
        return [
            'ternary_to_int' => $request->has('something') ? +1 : -1,
            'ternary_to_compound' => $this->resource->created_at ? ($this->resource->updated_at ? true : -1) : 'false',
            'short_ternary' => $this->resource->created_at?->toString() ?: 'false',
        ];
    }

    public function unknownComments(): array
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return [
            // @phpstan-ignore-next-line
            'propertyName' => $this->resource->what,
        ];
    }

    public function usingCasts(): array
    {
        return [
            'as_string' => (string)$this->resource->latestPost?->id,
            'as_int' => (int)$this->resource->getKey(),
            'as_bool' => (bool)$this->resource->created_at,
            'as_bool_not' => !$this->resource->created_at,
            'as_bool_and' => $this->resource->created_at && $this->resource->latestPost !== null,
            'as_bool_or' => $this->resource->created_at || $this->resource->latestPost !== null,
        ];
    }

    public function usingExplicitProperties(): array
    {
        return [
            'date' => $this->resource->explicitDate?->format('Y-m-d'),
            'promoted' => $this->resource->withPromoted->name,
        ];
    }

    public function usingParameter(Request $request): array
    {
        return [
            'path' => $request->headers->get('path'),
        ];
    }

    public function usingCollectionPluck(): array
    {
        return [
            'enum_all_without_pluck' => $this->resource->permissions->all(),
            'latest_post_ids' => $this->resource->latestPosts->pluck('id')->all(),
            'permissions' => $this->resource->permissions->pluck('value')->all(),
        ];
    }

    public function usingCollectionMap(): array
    {
        return [
            'permissions' => $this->resource->permissions->map(
                fn(Permission $permission) => $permission->value,
            )->all(),
        ];
    }

    public function usingResourceCollection(): array
    {
        return [
            'posts' => PostResource::collection($this->resource->latestPosts)->format(PostResource::SIMPLE),
        ];
    }

    public function usingWhen(): array
    {
        return [
            'no_fallback' => $this->when(
                !!$this->resource->created_at,
                fn() => $this->resource->created_at->format('Y-m-d'),
            ),
            'with_fallback' => $this->when(
                !!$this->resource->created_at,
                fn() => $this->resource->created_at->format('Y-m-d'),
                null,
            ),
        ];
    }

    public function usingWhenLoaded(): array
    {
        return [
            'no_fallback' => $this->whenLoaded('post', fn() => PostResource::make($this->resource->latestPost)
                ->format(PostResource::SIMPLE)),
            'with_fallback' => $this->whenLoaded('related', fn() => $this->resource->related->name, 'none'),
        ];
    }
}
