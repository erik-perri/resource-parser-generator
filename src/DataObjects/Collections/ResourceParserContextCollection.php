<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects\Collections;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use ResourceParserGenerator\DataObjects\ResourceContext;
use ResourceParserGenerator\Resolvers\ResourceResolver;
use ResourceParserGenerator\Types\Zod\ZodShapeReferenceType;
use RuntimeException;

class ResourceParserContextCollection
{
    /**
     * @param Collection<int, ResourceContext> $parserContexts
     */
    public function __construct(private readonly Collection $parserContexts)
    {
        //
    }

    /**
     * @param Collection<int, ResourceContext>|null $parserContexts
     * @return ResourceParserContextCollection
     */
    public static function create(Collection $parserContexts = null): self
    {
        return resolve(self::class, ['parserContexts' => $parserContexts ?? collect()]);
    }

    /**
     * @return Collection<int, ResourceContext>
     */
    public function collect(): Collection
    {
        return $this->parserContexts->collect();
    }

    public function concat(ResourceContext $context): self
    {
        return self::create($this->parserContexts->concat([$context]));
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceContext|null
     */
    public function find(string $className, string $methodName): ResourceContext|null
    {
        return $this->parserContexts->first(
            fn(ResourceContext $context) => $context->parserData->className() === $className
                && $context->parserData->methodName() === $methodName,
        );
    }

    /**
     * @return Collection<int, self>
     */
    public function splitToFiles(): Collection
    {
        return $this->parserContexts
            ->groupBy(function (ResourceContext $context) {
                if (!$context->configuration->outputFilePath) {
                    throw new RuntimeException(sprintf(
                        'Could not find output file path for "%s::%s"',
                        $context->parserData->className(),
                        $context->parserData->methodName(),
                    ));
                }
                return $context->configuration->outputFilePath;
            })
            ->map(fn(Collection $contexts) => new self($contexts));
    }

    public function updateLocalScope(
        ResourceParserContextCollection $localParsers,
        ResourceResolver $resourceResolver
    ): self {
        foreach ($this->parserContexts as $context) {
            foreach ($context->parserData->properties() as $propertyKey => $property) {
                if ($property instanceof ZodShapeReferenceType) {
                    $context->parserData->properties()->put($propertyKey, ZodShapeReferenceType::create(
                        $property->className,
                        $property->methodName,
                        $localParsers,
                        $resourceResolver,
                    ));
                }
            }
        }

        return $this;
    }

    /**
     * @param Closure(ResourceConfiguration $config): ResourceConfiguration $updater
     * @return self
     */
    public function updateConfiguration(Closure $updater): self
    {
        return static::create(
            $this->parserContexts->map(fn(ResourceContext $context) => new ResourceContext(
                $updater($context->configuration),
                $context->parserData,
            ))
        );
    }
}
