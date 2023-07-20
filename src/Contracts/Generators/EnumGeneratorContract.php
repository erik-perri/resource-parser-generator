<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use ResourceParserGenerator\DataObjects\EnumData;

interface EnumGeneratorContract
{
    /**
     * @param EnumData $enum
     * @return string
     */
    public function generate(EnumData $enum): string;
}
