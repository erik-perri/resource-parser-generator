<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Resources;

use ResourceParserGenerator\Tests\Examples\Models\Post;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Resource;

/**
 * @property-read Post $resource
 */
class PostResource extends Resource
{
    public const SIMPLE = 'simple';

    #[Format(self::SIMPLE)]
    public function base(): array
    {
        return [
            'status' => $this->resource->status->value,
        ];
    }
}
