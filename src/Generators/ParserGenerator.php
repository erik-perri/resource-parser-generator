<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\DataObjects\ParserData;

class ParserGenerator implements ParserGeneratorContract
{
    /**
     * @param Collection<int, ParserData> $parsers
     * @param ParserGeneratorContextContract $context
     * @return string
     */
    public function generate(Collection $parsers, ParserGeneratorContextContract $context): string
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
