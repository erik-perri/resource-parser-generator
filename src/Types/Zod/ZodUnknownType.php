<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;

class ZodUnknownType implements ParserTypeContract, ParserTypeWithCommentContract
{
    private ?string $comment = null;

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function constraint(): string
    {
        return 'unknown()';
    }

    public function imports(): array
    {
        return ['zod' => ['unknown']];
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment ? trim($comment) : null;
        $this->comment = $this->comment ?: null;

        return $this;
    }
}
