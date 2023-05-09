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
 * @property User $related
 *
 * @method string getRouteKey()
 *
 * @method static string getHintedStaticValue()
 */
class User extends AliasedLaravelModel
{
    public const CONST_STRING = 'string';
    public const CONST_FLOAT = 1.1;

    public function typedMethod(): int
    {
        return 1;
    }

    public function typedUnionMethod(): int|string
    {
        return 1;
    }

    /**
     * @return string
     */
    public function untypedWithDocMethod()
    {
        return 'string';
    }

    public function untypedWithoutDocMethod()
    {
        return 'string';
    }

    public static function getExplicitStaticValue(): int
    {
        return 1;
    }
}
