<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types\ArrayWithPropertiesType;

class ZodShapeType implements ParserTypeContract
{
    public function __construct(private readonly ArrayWithPropertiesType $properties)
    {
        //
    }

    public function constraint(): string
    {
        $properties = $this->properties->properties()->mapWithKeys(fn(TypeContract $type, string $key) => [
            $key => $type->parserType()->constraint(),
        ])->sort();

        return sprintf('object({%s})', $properties->join(', '));
    }

    public function imports(): array
    {
        $imports = collect(['zod' => ['object']]);

        foreach ($this->properties->properties() as $type) {
            $imports = $imports->mergeRecursive($type->parserType()->imports());
        }

        return $imports
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->all();
    }
}
