<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use ResourceParserGenerator\Builders\Constraints\ConstraintContract;

class ParserFileBuilder
{
    private array $parsers = [];

    public function addParser(ParserBuilder $parserBuilder): self
    {
        $this->parsers[] = $parserBuilder;

        return $this;
    }

    public function create(): string
    {
        return view('resource-parser-generator::resource-parser-file', [
            'imports' => $this->imports(),
            'parsers' => $this->parsers,
        ])->render();
    }

    public function imports(): array
    {
        return collect($this->parsers)
            ->map(fn(ParserBuilder $parser) => collect($parser->properties())
                ->map(fn(ConstraintContract $constraint) => $constraint->imports()))
            ->flatten()
            ->add('object')
            ->add('output')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }
}