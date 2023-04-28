<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\Types\BoolType;
use ResourceParserGenerator\Parsers\Types\FloatType;
use ResourceParserGenerator\Parsers\Types\IntType;
use ResourceParserGenerator\Parsers\Types\MixedType;
use ResourceParserGenerator\Parsers\Types\NullType;
use ResourceParserGenerator\Parsers\Types\ObjectType;
use ResourceParserGenerator\Parsers\Types\StringType;
use ResourceParserGenerator\Parsers\Types\UntypedType;
use ResourceParserGenerator\Parsers\Types\VoidType;
use RuntimeException;

class DeclaredTypeParser
{
    public function parse(ComplexType|Identifier|Name|null $type): TypeContract
    {
        if (!$type) {
            return new UntypedType();
        }

        if ($type instanceof Identifier) {
            return match ($type->name) {
                'int' => new IntType(),
                'string' => new StringType(),
                'float' => new FloatType(),
                'bool' => new BoolType(),
                'object' => new ObjectType(),
                'void' => new VoidType(),
                'null' => new NullType(),
                'mixed' => new MixedType(),
                default => throw new RuntimeException(sprintf('Unhandled identifier type "%s"', $type->name)),
            };
        }

        throw new RuntimeException(sprintf('Unhandled declared type "%s"', get_class($type)));
    }
}
