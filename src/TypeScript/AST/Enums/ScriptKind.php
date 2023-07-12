<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Enums;

enum ScriptKind: int
{
    case Unknown = 0;
    case JS = 1;
    case JSX = 2;
    case TS = 3;
    case TSX = 4;
    case External = 5;
    case JSON = 6;
    case Deferred = 7;
}
