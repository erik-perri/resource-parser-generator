<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;
use RuntimeException;

class ResourceDataCollection
{
    /**
     * @var Collection<int, ResourceData>
     */
    private readonly Collection $resources;

    public function __construct()
    {
        $this->resources = collect();
    }

    public function add(ResourceData $resource): self
    {
        $this->resources->add($resource);

        return $this;
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ?ResourceData
     */
    public function find(string $className, string $methodName): ?ResourceData
    {
        return $this->resources->first(
            fn(ResourceData $resource) => $resource->className === $className && $resource->methodName === $methodName,
        );
    }

    /**
     * @return Collection<string, Collection<int, ResourceData>>
     */
    public function splitToFiles(): Collection
    {
        /**
         * @var Collection<string, Collection<int, ResourceData>>
         */
        return $this->resources->groupBy(function (ResourceData $data) {
            if (!$data->configuration->parserFile) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $data->configuration->method[0],
                    $data->configuration->method[1],
                ));
            }

            return $data->configuration->parserFile;
        });
    }
}
