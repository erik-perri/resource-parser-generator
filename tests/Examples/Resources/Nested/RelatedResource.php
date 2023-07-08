<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Resources\Nested;

use ResourceParserGenerator\Tests\Examples\Models\User;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

/**
 * @property-read User $resource
 */
class RelatedResource extends Resource
{
    public const FORMAT_VERBOSE = 'verbose';

    #[IsDefault]
    public function base(): array
    {
        return [
            'name' => $this->resource->name,
        ];
    }

    #[Format('short')]
    public function shortFormatNotNamedLikeFormatName(): array
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
