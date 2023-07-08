<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\Types\ArrayWithPropertiesType;

class ZodShapeType implements ParserTypeContract
{
    public function __construct(
        private readonly ArrayWithPropertiesType $properties,
        private readonly ParserTypeConverterContract $parserTypeConverter,
    ) {
        //
    }

    public static function create(ArrayWithPropertiesType $properties): self
    {
        return resolve(self::class, ['properties' => $properties]);
    }

    public function constraint(): string
    {
        $properties = $this->properties->properties()->mapWithKeys(fn(TypeContract $type, string $key) => [
            $key => $this->parserTypeConverter->convert($type)->constraint(),
        ])->sort();

        return sprintf('object({%s})', $properties
            ->map(fn(string $type, string $key) => sprintf('%s: %s', $key, $type))
            ->join(', '));
    }

    public function imports(): ImportCollectionContract
    {
        $imports = new ImportCollection(new Import('object', 'zod'));

        foreach ($this->properties->properties() as $type) {
            $parserType = $this->parserTypeConverter->convert($type);
            $imports = $imports->merge($parserType->imports());
        }

        return $imports;
    }
}
