<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;

class ResourceParserGenerator
{
    /**
     * @param ResourceParserCollection $parsers
     * @return string
     */
    public function build(ResourceParserCollection $parsers): string
    {
        $imports = $parsers
            ->flatten()
            ->map(fn(ResourceParserData $parser) => $parser->properties()
                ->map(fn(ParserTypeContract $property) => $property->imports()))
            ->flatten()
            ->add('object')
            ->add('output')
            ->unique()
            ->sort();

        $content = view('resource-parser-generator::resource-parser-file', [
            'imports' => $imports->all(),
            'parsers' => $parsers->flatten(),
        ])->render();

        return trim($content) . PHP_EOL;
    }
}
