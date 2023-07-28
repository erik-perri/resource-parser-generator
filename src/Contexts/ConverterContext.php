<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contexts;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

/**
 * This contains the resolve context for the current conversion (variables and `this`) as well as properties that have
 * been marked as non-null for the rest of the processing (by `when` for example).
 *
 * TODO Adjust non-null properties to support nested properties so we can support un-nulling a chain of fetches.
 *      Right now we un-null by individual property names but that could un-null a property that is not actually
 *      non-null. `->when('property', fn($m) => $m->property->property)` would un-null both properties when only the
 *      first is expected.
 */
class ConverterContext
{
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
}
