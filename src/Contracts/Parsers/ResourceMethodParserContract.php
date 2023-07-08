<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Parsers;

use ResourceParserGenerator\DataObjects\ResourceDataCollection;
use ResourceParserGenerator\DataObjects\ResourceGeneratorConfiguration;

interface ResourceMethodParserContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceDataCollection $resources
     * @param ResourceGeneratorConfiguration $configuration
     * @return void
     */
    public function parse(
        string $className,
        string $methodName,
        ResourceDataCollection $resources,
        ResourceGeneratorConfiguration $configuration,
    ): void;
}
