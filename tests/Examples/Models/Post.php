<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Models;

use Illuminate\Database\Eloquent\Model;
use ResourceParserGenerator\Tests\Examples\Enums\LegacyPostStatus;
use ResourceParserGenerator\Tests\Examples\Enums\PostStatus;

/**
 * @property-read int $id
 * @property-read PostStatus $status
 * @property-read LegacyPostStatus $legacyStatus
 *
 * @method int getRouteKey()
 */
class Post extends Model
{
    //
}
