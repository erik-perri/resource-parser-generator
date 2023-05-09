<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples;

use ResourceParserGenerator\Tests\Examples\Models\User;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Resource;

/**
 * @property-read User $resource
 */
class RelatedResource extends Resource
{
    public const FORMAT_BASE = 'base';
    public const FORMAT_VERBOSE = 'verbose';

    #[Format(self::FORMAT_BASE)]
    public function base(): array
    {
        return [
            'id' => $this->resource->id,
        ];
    }

    #[Format(self::FORMAT_VERBOSE)]
    public function verbose(): array
    {
        return [
            'id' => $this->resource->id,
            'email' => $this->resource->email,
        ];
    }
}
