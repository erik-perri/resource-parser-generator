<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
