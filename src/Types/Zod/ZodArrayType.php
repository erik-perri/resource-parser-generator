<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ResourceGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\Types\Traits\HasCommentTrait;
use RuntimeException;

class ZodArrayType implements ParserTypeContract, ParserTypeWithCommentContract
{
    use HasCommentTrait;

    public function __construct(
        public readonly ParserTypeContract|null $keys,
        public readonly ParserTypeContract|null $values,
    ) {
        //
    }

    public function comment(): ?string
    {
        $imploded = collect([
            $this->comment,
            $this->keys instanceof ParserTypeWithCommentContract ? $this->keys->comment() : null,
            $this->values instanceof ParserTypeWithCommentContract ? $this->values->comment() : null,
        ])
            ->filter()
            ->implode("\n");

        return trim($imploded) ?: null;
    }

    public function constraint(ResourceGeneratorContextContract $context): string
    {
        if ($this->keys && $this->values) {
            return sprintf('record(%s, %s)', $this->keys->constraint($context), $this->values->constraint($context));
        }

        if ($this->values) {
            return sprintf('array(%s)', $this->values->constraint($context));
        }

        throw new RuntimeException('Untyped Zod arrays are not supported');
    }

    public function imports(ResourceGeneratorContextContract $context): ImportCollectionContract
    {
        $constraintType = $this->keys && $this->values
            ? 'record'
            : 'array';

        $imports = new ImportCollection(new Import($constraintType, 'zod'));

        if ($this->keys) {
            $imports = $imports->merge($this->keys->imports($context));
        }

        if ($this->values) {
            $imports = $imports->merge($this->values->imports($context));
        }

        return $imports;
    }
}
