<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;
use ResourceParserGenerator\Types\Zod\ZodShapeReferenceType;

class ResourceParserGenerator
{
    /**
     * @param Collection<int, ResourceParserData> $parsers
     * @return string
     */
    public function generate(Collection $parsers): string
    {
        $imports = collect();
        foreach ($parsers as $parser) {
            foreach ($parser->properties() as $property) {
                if ($property instanceof ZodShapeReferenceType) {
                    $imports = $imports->mergeRecursive($property->shapeImport($parsers));
                } else {
                    $imports = $imports->mergeRecursive($property->imports());
                }
            }
        }

        $imports = $imports->mergeRecursive(['zod' => ['object', 'output']])
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->sort();

        $content = view('resource-parser-generator::resource-parser-file', [
            'imports' => $imports,
            'parsers' => $parsers,
        ])->render();

        return trim($content) . PHP_EOL;
    }
}
