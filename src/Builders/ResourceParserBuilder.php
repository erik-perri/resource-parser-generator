<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Builders\Data\ParserData;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Parsers\Data\ParsedResource;

class ResourceParserBuilder
{
    public function __construct(
        private readonly ParserNameGenerator $parserNameGenerator,
    ) {
        //
    }

    /**
     * @param Collection<string, ParsedResource> $parsedResources
     * @return string
     */
    public function build(Collection $parsedResources): string
    {
        $imports = $parsedResources
            ->map(fn(ParsedResource $parser) => $parser->type()
                ->properties()
                ->map(fn(TypeContract $property) => $property->parserType()->imports()))
            ->flatten()
            ->add('object')
            ->add('output')
            ->unique()
            ->sort();

        $parsers = $parsedResources->map(fn(ParsedResource $parser) => new ParserData(
            $this->parserNameGenerator->generateVariableName(
                $parser->fullyQualifiedResourceName(),
                $parser->format(),
            ),
            $this->parserNameGenerator->generateTypeName(
                $parser->fullyQualifiedResourceName(),
                $parser->format(),
            ),
            $parser->type()->properties()->map(fn(TypeContract $property) => $property->parserType())
        ));

        $content = view('resource-parser-generator::resource-parser-file', [
            'imports' => $imports->all(),
            'parsers' => $parsers,
        ])->render();

        return trim($content) . PHP_EOL;
    }
}
