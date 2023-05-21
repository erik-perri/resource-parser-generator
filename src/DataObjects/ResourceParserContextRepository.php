<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceParserContextRepositoryContract;
use RuntimeException;

class ResourceParserContextRepository implements ResourceParserContextRepositoryContract
{
    /**
     * @var Collection<int, ResourceContext>
     */
    private Collection $globalParsers;

    /**
     * @var Collection<int, ResourceContext>
     */
    private Collection $localParsers;

    public function __construct()
    {
        $this->globalParsers = collect();
        $this->localParsers = collect();
    }

    public function add(ResourceContext $resourceContext): self
    {
        $this->globalParsers->push($resourceContext);

        return $this;
    }

    public function findGlobal(string $className, string $methodName): ResourceContext|null
    {
        return $this->globalParsers->first(
            fn(ResourceContext $context) => $context->parserData->className() === $className
                && $context->parserData->methodName() === $methodName,
        );
    }

    public function findLocal(string $className, string $methodName): ResourceContext|null
    {
        return $this->localParsers->first(
            fn(ResourceContext $context) => $context->parserData->className() === $className
                && $context->parserData->methodName() === $methodName,
        );
    }

    public function setLocalContext(Collection $localParsers): self
    {
        $this->localParsers = $localParsers->collect();

        return $this;
    }

    public function splitToFiles(): Collection
    {
        /**
         * @var Collection<string, Collection<int, ResourceContext>>
         */
        return $this->globalParsers->groupBy(function (ResourceContext $context) {
            if (!$context->configuration->outputFilePath) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $context->parserData->className(),
                    $context->parserData->methodName(),
                ));
            }
            return $context->configuration->outputFilePath;
        });
    }

    public function updateConfiguration(Closure $updater): self
    {
        $this->globalParsers = $this->globalParsers->map(function (ResourceContext $context) use ($updater) {
            return new ResourceContext(
                $updater($context->configuration),
                $context->parserData,
            );
        });

        return $this;
    }
}
