<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

class ConverterContext
{
    private string|null $formatMethod = null;

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $nonNullProperties;

    /**
     * @param ResolverContract $resolver
     * @param array<int, string> $nonNullProperties
     */
    public function __construct(private readonly ResolverContract $resolver, array $nonNullProperties = [])
    {
        $this->nonNullProperties = collect($nonNullProperties);
    }

    public function formatMethod(): string|null
    {
        return $this->formatMethod;
    }

    public function isPropertyNonNull(string $property): bool
    {
        return $this->nonNullProperties->some(fn(string $allowed) => $allowed === $property);
    }

    public function resolver(): ResolverContract
    {
        return $this->resolver;
    }

    public function setFormatMethod(string $format): self
    {
        $this->formatMethod = $format;

        return $this;
    }
}
