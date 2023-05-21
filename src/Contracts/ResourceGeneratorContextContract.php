<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;

interface ResourceGeneratorContextContract
{
    public function add(ResourceData $resourceContext): self;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceData|null
     */
    public function findGlobal(string $className, string $methodName): ResourceData|null;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceData|null
     */
    public function findLocal(string $className, string $methodName): ResourceData|null;

    /**
     * @param Collection<int, ResourceData> $localParsers
     * @return self
     */
    public function setLocalContext(Collection $localParsers): self;

    /**
     * @template T
     * @param Collection<int, ResourceData> $localParsers
     * @param Closure(): T $callback
     * @return T
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed;

    /**
     * @return Collection<string, Collection<int, ResourceData>>
     */
    public function splitToFiles(): Collection;

    /**
     * @param Closure(ResourceConfiguration $config): ResourceConfiguration $updater
     * @return self
     */
    public function updateConfiguration(Closure $updater): self;
}
