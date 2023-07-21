<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\Traits\HasCommentTrait;

class UntypedType implements TypeContract
{
    use HasCommentTrait;

    public function describe(): string
    {
        return 'untyped';
    }
}
