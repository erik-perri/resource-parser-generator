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
    /**
     * In Draft
     */
    const DRAFT = 'draft';
    const PUBLISHED = 'published';
    /**
     * With
     *
     * New Lines
     */
    const ARCHIVED = 'archived';
}
