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
     * @param ParserData $parser
     * @param Collection<int, ParserData> $otherParsers
     * @return string
     */
    public function generate(ParserData $parser, Collection $otherParsers): string
    {
        // TODO Make these imports configurable.
        $imports = new ImportCollection(
            new Import('object', 'zod'),
            new Import('output', 'zod'),
        );

        $generatorContext = resolve(ParserGeneratorContextContract::class, [
            'parsers' => $otherParsers,
        ]);

        foreach ($parser->properties as $property) {
            $imports = $imports->merge($property->imports($generatorContext));
        }

        $content = view('resource-parser-generator::resource-parser-file', [
            'context' => $generatorContext,
            'imports' => $imports->groupForView(),
            'parser' => $parser,
        ])->render();

        return trim($content) . "\n";
    }
}
