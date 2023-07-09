<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\Types\Traits\HasCommentTrait;

class ZodUnionType implements ParserTypeContract, ParserTypeWithCommentContract
{
    use HasCommentTrait;

    /**
     * @var Collection<int|string, ParserTypeContract>
     */
    private readonly Collection $types;

    public function __construct(ParserTypeContract ...$types)
    {
        $this->types = collect($types);
    }

    public function comment(): ?string
    {
        $imploded = $this->types->map(
            fn(ParserTypeContract $type) => $type instanceof ParserTypeWithCommentContract
                ? $type->comment()
                : null,
        )
            ->prepend($this->comment)
            ->filter()
            ->implode("\n");

        return trim($imploded) ?: null;
    }

    public function constraint(ParserGeneratorContextContract $context): string
    {
        $types = $this->types->map(fn(ParserTypeContract $type) => $type->constraint($context))
            ->unique()
            ->sort();

        if ($types->count() === 1) {
            return $types->firstOrFail();
        }

        return sprintf('union([%s])', $types->join(', '));
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        $types = $this->types->map(fn(ParserTypeContract $type) => $type->constraint($context))
            ->unique()
            ->sort();

        $imports = $types->count() > 1
            ? new ImportCollection(new Import('union', 'zod'))
            : new ImportCollection();

        foreach ($this->types as $type) {
            $imports = $imports->merge($type->imports($context));
        }

        return $imports;
    }

    /**
     * @return Collection<int|string, ParserTypeContract>
     */
    public function types(): Collection
    {
        return $this->types->collect();
    }
}
