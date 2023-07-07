<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Traits;

trait HasCommentTrait
{
    protected ?string $comment = null;

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment ? trim($comment) : null;
        $this->comment = $this->comment ?: null;

        return $this;
    }
}
