<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

use ResourceParserGenerator\Contracts\ImportCollectionContract;

interface ParserTypeContract
{
    /**
     * @return ImportCollectionContract
     */
    public function imports(): ImportCollectionContract;

    public function constraint(): string;
}
