<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

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
