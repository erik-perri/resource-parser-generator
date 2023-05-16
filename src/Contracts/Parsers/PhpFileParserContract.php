<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\Parsers\Data\FileScope;

interface PhpFileParserContract
{
    /**
     * @param string $contents
     * @param class-string|null $staticContext
     * @return FileScope
     */
    public function parse(string $contents, string $staticContext = null): FileScope;
}
