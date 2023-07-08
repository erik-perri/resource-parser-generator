<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use RuntimeException;

class ResourceGeneratorContext implements ResourceGeneratorContextContract
{
    private ResourceGeneratorConfiguration $configuration;

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

    /**
     * @return ResourceGeneratorConfiguration
     */
    public function configuration(): ResourceGeneratorConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(ResourceGeneratorConfiguration $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
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
            $configuration = $this->configuration->parserConfiguration($context->className(), $context->methodName());

            if (!$configuration->parserFile) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $context->className(),
                    $context->methodName(),
                ));
            }

            return $configuration->parserFile;
        });
    }
}
