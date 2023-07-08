<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

/**
 * This contains information known about the current context of the converter. For example when parsing a chain of a
 * resource we may encounter a format method for a resource, this is where we store that to process once the chain is
 * completely processed.
 *
 * Ideally we would have the returned type data contain that instead, but it is not structured in a way to easily
 * support that yet.
 *
 * TODO Restructure to not need this in favor of a custom ClassType with additional data?
 * TODO Adjust non-null properties to support nested properties so we can support un-nulling a chain of fetches.
 *          Right now we un-null by individual property names but that could un-null a property that is not actually
 *          non-null. `->when('property', fn($m) => $m->property->property)` would un-null both properties when only the
 *          first is expected.
 */
class ConverterContext
{
    private string|null $formatMethod = null;
    private bool $isCollection = false;

    /**
     * @param ResolverContract $resolver
     * @param Collection<int, string> $nonNullProperties
     */
    public function __construct(
        private readonly ResolverContract $resolver,
        private readonly Collection $nonNullProperties,
    ) {
        //
    }

    /**
     * @param ResolverContract $resolver
     * @param Collection<int, string>|null $nonNullProperties
     * @return self
     */
    public static function create(ResolverContract $resolver, Collection|null $nonNullProperties = null): self
    {
        return resolve(self::class, [
            'resolver' => $resolver,
            'nonNullProperties' => $nonNullProperties ?? collect(),
        ]);
    }

    public function formatMethod(): string|null
    {
        return $this->formatMethod;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function isPropertyNonNull(string $property): bool
    {
        return $this->nonNullProperties->some(fn(string $allowed) => $allowed === $property);
    }

    /**
     * @return Collection<int, string>
     */
    public function nonNullProperties(): Collection
    {
        return $this->nonNullProperties->collect();
    }

    public function resolver(): ResolverContract
    {
        return $this->resolver;
    }

    public function setFormatMethod(string|null $format): self
    {
        $this->formatMethod = $format;

        return $this;
    }

    public function setIsCollection(bool $isCollection): self
    {
        $this->isCollection = $isCollection;

        return $this;
    }
}
