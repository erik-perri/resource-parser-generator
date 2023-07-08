<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceData;

interface ResourceGeneratorContextContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceData|null
     */
    public function find(string $className, string $methodName): ResourceData|null;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceData|null
     */
    public function findLocal(string $className, string $methodName): ResourceData|null;

    /**
     * @template T
     * @param Collection<int, ResourceData> $localParsers
     * @param Closure(): T $callback
     * @return T
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed;
}
