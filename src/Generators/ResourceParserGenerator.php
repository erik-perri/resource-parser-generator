<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;
use ResourceParserGenerator\Types\Zod\ZodShapeReferenceType;

class ResourceParserGenerator
{
    /**
     * @param ResourceParserCollection $parsers
     * @return string
     */
    public function build(ResourceParserCollection $parsers): string
    {
        /**
         * @var Collection<string, ResourceParserData> $parsers
         */
        $parsers = $parsers->flatten();

        // TODO Figure out a way to restructure this so we're passing in the already generated file and variable names
        //      rather than regenerating them in shapeImport.
        $availableParsersInThisFile = $parsers
            ->map(fn(ResourceParserData $parser) => $parser->variableName())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $imports = collect();
        foreach ($parsers as $parser) {
            foreach ($parser->properties() as $property) {
                if ($property instanceof ZodShapeReferenceType) {
                    $imports = $imports->mergeRecursive($property->shapeImport($availableParsersInThisFile));
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
