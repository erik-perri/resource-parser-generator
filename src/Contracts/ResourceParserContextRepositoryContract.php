<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceContext;

interface ResourceParserContextRepositoryContract
{
    public function add(ResourceContext $resourceContext): self;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceContext|null
     */
    public function findGlobal(string $className, string $methodName): ResourceContext|null;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceContext|null
     */
    public function findLocal(string $className, string $methodName): ResourceContext|null;

    /**
     * @param Collection<int, ResourceContext> $localParsers
     * @return self
     */
    public function setLocalContext(Collection $localParsers): self;

    /**
     * @return Collection<string, Collection<int, ResourceContext>>
     */
    public function splitToFiles(): Collection;

    /**
     * @param Closure(ResourceConfiguration $config): ResourceConfiguration $updater
     * @return self
     */
    public function updateConfiguration(Closure $updater): self;
}
