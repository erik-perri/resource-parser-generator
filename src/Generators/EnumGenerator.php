<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\EnumGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumData;

class EnumGenerator implements EnumGeneratorContract
{
    /**
     * @param EnumData $enum
     * @return string
     */
    public function generate(EnumData $enum): string
    {
        // TODO Generate enum file from EnumData objects
        return '';
    }
}
