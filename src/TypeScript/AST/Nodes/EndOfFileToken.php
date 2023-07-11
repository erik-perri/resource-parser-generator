<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

use ResourceParserGenerator\TypeScript\AST\Enums\SyntaxKind;

class EndOfFileToken extends BaseNode
{
    public function kind(): SyntaxKind
    {
        return SyntaxKind::EndOfFileToken;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind()->value,
        ];
    }
}
