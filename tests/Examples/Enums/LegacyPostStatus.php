<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static READ()
 * @method static static WRITE()
 * @method static static DELETE()
 */
class LegacyPostStatus extends Enum
{
    const DRAFT = 'draft';
    const PUBLISHED = 'published';
    const ARCHIVED = 'archived';
}
