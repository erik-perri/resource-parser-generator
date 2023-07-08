<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\DataObjects\ResourceDataCollection;

/**
 * This class is used to track the local scope when generating the parser files.
 */
class ResourceGeneratorContext implements ResourceGeneratorContextContract
{
    /**
     * @var Collection<int, ResourceData>
     */
    private Collection $localResources;

    public function __construct(private readonly ResourceDataCollection $resources)
    {
        $this->localResources = collect();
    }

    public function find(string $className, string $methodName): ResourceData|null
    {
        return $this->resources->find($className, $methodName);
    }

    public function findLocal(string $className, string $methodName): ResourceData|null
    {
        return $this->localResources->first(
            fn(ResourceData $context) => $context->className === $className && $context->methodName === $methodName,
        );
    }

    /**
     * @template T
     * @param Collection<int, ResourceData> $localParsers
     * @param Closure(): T $callback
     * @return T
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed
    {
        $this->localResources = $localParsers->collect();
        $result = $callback();
        $this->localResources = collect();

        return $result;
    }
}
