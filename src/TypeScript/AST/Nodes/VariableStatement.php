<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

use ResourceParserGenerator\TypeScript\AST\Enums\SyntaxKind;
use ResourceParserGenerator\TypeScript\AST\Nodes\Collections\ModifierNodeCollection;

class VariableStatement extends BaseNode
{
    public function __construct(
        public readonly VariableDeclarationList $declarationList,
        public readonly ?ModifierNodeCollection $modifiers = null,
    ) {
        //
    }

    public function kind(): SyntaxKind
    {
        return SyntaxKind::VariableStatement;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind()->value,
            'declarationList' => $this->declarationList->toArray(),
        ];
    }
}
