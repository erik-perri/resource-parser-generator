<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

enum PostStatus: string
{
    /**
     * Draft
     */
    case Draft = 'draft';
    case Published = 'published';
    /**
     * With
     *
     * New Lines
     */
    case Archived = 'archived';
}
