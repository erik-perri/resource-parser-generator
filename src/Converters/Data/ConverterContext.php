<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Data;

use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;

class ConverterContext
{
    private string|null $formatMethod = null;

    public function __construct(private readonly ResolverContract $resolver)
    {
        //
    }

    public function formatMethod(): string|null
    {
        return $this->formatMethod;
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
