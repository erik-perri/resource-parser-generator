<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Types;

interface ParserTypeWithCommentContract
{
    public function comment(): ?string;

    public function setComment(?string $comment): self;
}
