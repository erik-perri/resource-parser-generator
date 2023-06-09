<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as AliasedLaravelModel;
use Illuminate\Support\Collection as BaseCollection;
use ResourceParserGenerator\Tests\Examples\DataObjects\WithPromotedProperties;
use ResourceParserGenerator\Tests\Examples\Enums\Permission;
use ResourceParserGenerator\Tests\Examples\Enums\Role;

/**
 * @property-read int $id
 * @property-read string $ulid
 * @property string $email
 * @property string $name
 * @property Role $role
 * @property CarbonImmutable|null $created_at
 * @property ?CarbonImmutable $updated_at
 * @property WithPromotedProperties $withPromoted
 *
 * @property-read User|null $related
 *
 * @property-read Post|null $latestPost
 * @property-read Collection<int, Post> $latestPosts
 * @property-read BaseCollection<int, Permission> $permissions
 *
 * @method string getRouteKey()
 *
 * @method static string getHintedStaticValue()
 */
class User extends AliasedLaravelModel
{
    public const CONST_STRING = 'string';
    public const CONST_FLOAT = 1.1;

    public ?CarbonImmutable $explicitDate;

    public static function getExplicitStaticValue(): int
    {
        return 1;
    }
}
