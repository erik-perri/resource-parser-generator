<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ResourceContext
{
    public function __construct(
        public readonly ResourceConfiguration $configuration,
        public readonly ResourceMethodData $parserData,
    ) {
        //
    }
}
