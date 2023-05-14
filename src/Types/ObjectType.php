<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use RuntimeException;

class ObjectType implements TypeContract
{
    public function describe(): string
    {
        return 'object';
    }

    public function parserType(): ParserTypeContract
    {
        throw new RuntimeException(class_basename(self::class) . ' cannot be converted to parser type.');
    }
}
