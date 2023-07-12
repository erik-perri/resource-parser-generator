<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

use ResourceParserGenerator\TypeScript\AST\Enums\Collections\NodeFlagCollection;
use ResourceParserGenerator\TypeScript\AST\Enums\NodeFlag;
use ResourceParserGenerator\TypeScript\AST\Enums\SyntaxKind;

class VariableDeclarationList extends BaseNode
{
    public NodeFlagCollection $nodeFlags;

    public function __construct(
        /** @phpstan-ignore-next-line  */
        public readonly VariableDeclarationCollection $declarations,
        NodeFlagCollection $nodeFlags = new NodeFlagCollection([NodeFlag::None]),
    ) {
        $this->nodeFlags = $nodeFlags->push(NodeFlag::BlockScoped);
    }

    public function kind(): SyntaxKind
    {
        return SyntaxKind::VariableDeclarationList;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind()->value,
//            'declarations' => $this->declarations->toArray(),
            'flags' => $this->nodeFlags->toInt(),
        ];
    }
}
