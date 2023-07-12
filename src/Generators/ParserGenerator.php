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
     * @param Collection<int, ParserData> $localParsers
     * @param Collection<int, ParserData> $globalParsers
     * @return string
     */
    public function generate(Collection $localParsers, Collection $globalParsers): string
    {
        // TODO Make these imports configurable.
        $imports = new ImportCollection(
            new Import('object', 'zod'),
            new Import('output', 'zod'),
        );

        $generatorContext = resolve(ParserGeneratorContextContract::class, [
            'globalParsers' => $globalParsers,
            'localParsers' => $localParsers,
        ]);

        foreach ($localParsers as $parser) {
            foreach ($parser->properties as $property) {
                $imports = $imports->merge($property->imports($generatorContext));
            }
        }

        $content = view('resource-parser-generator::resource-parser-file', [
            'context' => $generatorContext,
            'imports' => $imports->groupForView(),
            'parsers' => $localParsers->collect(),
        ])->render();

        return trim($content) . "\n";
    }
}
