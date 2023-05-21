<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use RuntimeException;

class ResourceGeneratorContext implements ResourceGeneratorContextContract
{
    /**
     * @var Collection<int, ResourceData>
     */
    private Collection $globalParsers;

    /**
     * @var Collection<int, ResourceData>
     */
    private Collection $localParsers;

    public function __construct()
    {
        $this->globalParsers = collect();
        $this->localParsers = collect();
    }

    public function add(ResourceData $resourceContext): self
    {
        $this->globalParsers->push($resourceContext);

        return $this;
    }

    public function findGlobal(string $className, string $methodName): ResourceData|null
    {
        return $this->globalParsers->first(
            fn(ResourceData $context) => $context->className() === $className && $context->methodName() === $methodName,
        );
    }

    public function findLocal(string $className, string $methodName): ResourceData|null
    {
        return $this->localParsers->first(
            fn(ResourceData $context) => $context->className() === $className && $context->methodName() === $methodName,
        );
    }

    public function setLocalContext(Collection $localParsers): self
    {
        $this->localParsers = $localParsers->collect();

        return $this;
    }

    /**
     * @param Collection<int, ResourceData> $localParsers
     * @param Closure $callback
     * @return mixed
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed
    {
        $this->localParsers = $localParsers->collect();
        $result = $callback();
        $this->localParsers = collect();

        return $result;
    }

    public function splitToFiles(): Collection
    {
        /**
         * @var Collection<string, Collection<int, ResourceData>>
         */
        return $this->globalParsers->groupBy(function (ResourceData $context) {
            if (!$context->configuration->outputFilePath) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $context->className(),
                    $context->methodName(),
                ));
            }
            return $context->configuration->outputFilePath;
        });
    }

    public function updateConfiguration(Closure $updater): self
    {
        $this->globalParsers = $this->globalParsers->map(
            fn(ResourceData $resource) => $resource->updateConfiguration($updater($resource->configuration)),
        );

        return $this;
    }
}
