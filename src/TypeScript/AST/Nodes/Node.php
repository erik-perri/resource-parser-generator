<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

use Illuminate\Contracts\Support\Arrayable;
use ResourceParserGenerator\TypeScript\AST\Enums\SyntaxKind;

/**
 * @extends Arrayable<array-key, mixed>
 */
interface Node extends Arrayable
{
    public function kind(): SyntaxKind;
    public function toJson(): string;
}
