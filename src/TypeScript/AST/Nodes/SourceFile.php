<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

use ResourceParserGenerator\TypeScript\AST\Enums\ScriptKind;
use ResourceParserGenerator\TypeScript\AST\Enums\SyntaxKind;
use ResourceParserGenerator\TypeScript\AST\Nodes\Collections\NodeCollection;

class SourceFile extends BaseNode
{
    public function __construct(
        public readonly NodeCollection $statements,
        public readonly ScriptKind $scriptKind = ScriptKind::TS,
    ) {
        //
    }

    public function kind(): SyntaxKind
    {
        return SyntaxKind::SourceFile;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind()->value,
            'endOfFileToken' => (new EndOfFileToken)->toArray(),
            'scriptKind' => $this->scriptKind->value,
            'statements' => $this->statements->toArray(),
            'text' => '',
        ];
    }
}
