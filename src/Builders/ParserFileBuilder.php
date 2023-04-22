<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Builders\Constraints\ConstraintContract;

class ParserFileBuilder
{
    /**
     * @var array<ParserBuilder>
     */
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

    /**
     * @return string[]
     */
    public function imports(): array
    {
        /** @var Collection<int, string> $imports */
        $imports = collect($this->parsers)
            ->map(fn(ParserBuilder $parser) => collect($parser->properties())
                ->map(fn(ConstraintContract $constraint) => $constraint->imports()))
            ->flatten()
            ->add('object')
            ->add('output')
            ->unique()
            ->sort()
            ->values();

        return $imports->all();
    }
}
