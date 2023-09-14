<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Nodes;

abstract class BaseNode implements Node
{
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
