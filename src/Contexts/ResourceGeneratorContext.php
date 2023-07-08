<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\DataObjects\ResourceGeneratorConfiguration;
use ResourceParserGenerator\Generators\ParserConfigurationGenerator;
use RuntimeException;

/**
 * This is a singleton containing the information about the parsed resources. It allows switching of the local context
 * so when we're rendering individual files we can determine which parsers need to be imported and which are available
 * in the local scope.
 */
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

    public function __construct(
        private readonly ResourceGeneratorConfiguration $configuration,
        private readonly ParserConfigurationGenerator $parserConfigurationGenerator,
    ) {
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
            fn(ResourceData $context) => $context->className === $className && $context->methodName === $methodName,
        );
    }

    public function findLocal(string $className, string $methodName): ResourceData|null
    {
        return $this->localParsers->first(
            fn(ResourceData $context) => $context->className === $className && $context->methodName === $methodName,
        );
    }

    /**
     * @return ResourceGeneratorConfiguration
     */
    public function configuration(): ResourceGeneratorConfiguration
    {
        return $this->configuration;
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
            $configuration = $this->parserConfigurationGenerator->generate(
                $this->configuration,
                $context->className,
                $context->methodName,
            );

            if (!$configuration->parserFile) {
                throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $context->className,
                    $context->methodName,
                ));
            }

            return $configuration->parserFile;
        });
    }
}
