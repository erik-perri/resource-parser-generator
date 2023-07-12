<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\EnumData;

interface EnumGeneratorContract
{
    /**
     * @param Collection<int, EnumData> $enums
     * @return string
     */
    public function generate(Collection $enums): string;
}
