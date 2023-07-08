<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\Types\Traits\HasCommentTrait;

class ZodIntersectionType implements ParserTypeContract, ParserTypeWithCommentContract
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

    public function constraint(): string
    {
        $types = $this->types->map(fn(ParserTypeContract $type) => $type->constraint())
            ->unique()
            ->sort();

        if ($types->count() === 1) {
            return $types->firstOrFail();
        }

        return sprintf('intersection([%s])', $types->join(', '));
    }

    public function imports(): ImportCollectionContract
    {
        $imports = new ImportCollection(new Import('intersection', 'zod'));

        foreach ($this->types as $type) {
            $imports = $imports->merge($type->imports());
        }

        return $imports;
    }
}
