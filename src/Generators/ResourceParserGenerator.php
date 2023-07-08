<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\ResourceParserGeneratorContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\DataObjects\ResourceData;

class ResourceParserGenerator implements ResourceParserGeneratorContract
{
    /**
     * @param Collection<int, ResourceData> $parsers
     * @param ResourceGeneratorContextContract $context
     * @return string
     */
    public function generate(Collection $parsers, ResourceGeneratorContextContract $context): string
    {
        // TODO Make these imports configurable.
        $imports = new ImportCollection(
            new Import('object', 'zod'),
            new Import('output', 'zod'),
        );

        foreach ($parsers as $parser) {
            foreach ($parser->properties as $property) {
                $imports = $imports->merge($property->imports($context));
            }
        }

        $content = view('resource-parser-generator::resource-parser-file', [
            'context' => $context,
            'imports' => $imports->groupForView(),
            'parsers' => $parsers->collect(),
        ])->render();

        return trim($content) . "\n";
    }
}
