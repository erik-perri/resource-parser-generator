<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodShapeType implements ParserTypeContract
{
    /**
     * @param Collection<string, ParserTypeContract> $properties
     */
    public function __construct(
        private readonly Collection $properties,
    ) {
        //
    }

    public function constraint(ParserGeneratorContextContract $context): string
    {
        return sprintf('object({%s})', $this->properties
            ->map(fn(ParserTypeContract $type, string $key) => sprintf('%s: %s', $key, $type->constraint($context)))
            ->join(', '));
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        $imports = new ImportCollection(new Import('object', 'zod'));

        foreach ($this->properties as $parserType) {
            $imports = $imports->merge($parserType->imports($context));
        }

        return $imports;
    }
}
