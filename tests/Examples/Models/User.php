<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model as AliasedLaravelModel;

/**
 * @property-read int $id
 * @property-read string $ulid
 * @property string $email
 * @property string $name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @property-read User|null $related
 *
 * @method string getRouteKey()
 *
 * @method static string getHintedStaticValue()
 */
class User extends AliasedLaravelModel
{
    public const CONST_STRING = 'string';
    public const CONST_FLOAT = 1.1;

    public static function getExplicitStaticValue(): int
    {
        return 1;
    }
}
