<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\EnumGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumData;

class EnumGenerator implements EnumGeneratorContract
{
    /**
     * @param Collection<int, EnumData> $enums
     * @return string
     */
    public function generate(Collection $enums): string
    {
        // TODO Generate enum file from EnumData objects
        return '';
    }
}
