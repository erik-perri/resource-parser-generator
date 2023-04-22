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
 * @method string getRouteKey()
 */
class User extends AliasedLaravelModel
{
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
}
