<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\Types\ArrayType;
use ResourceParserGenerator\Parsers\Types\BoolType;
use ResourceParserGenerator\Parsers\Types\ClassType;
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
    public function parse(ComplexType|Identifier|Name|null $type, ClassNameResolverContract $resolver): TypeContract
    {
        if (!$type) {
            return new UntypedType();
        }

        if ($type instanceof Identifier) {
            return match ($type->name) {
                'array' => new ArrayType(null),
                'bool' => new BoolType(),
                'float' => new FloatType(),
                'int' => new IntType(),
                'mixed' => new MixedType(),
                'null' => new NullType(),
                'object' => new ObjectType(),
                'string' => new StringType(),
                'void' => new VoidType(),
                default => throw new RuntimeException(sprintf('Unhandled identifier type "%s"', $type->name)),
            };
        }

        if ($type instanceof UnionType) {
            return new Types\UnionType(
                ...array_map(fn(ComplexType|Identifier|Name $type) => $this->parse($type, $resolver), $type->types),
            );
        }

        if ($type instanceof NullableType) {
            return new Types\UnionType(
                new NullType(),
                $this->parse($type->type, $resolver),
            );
        }

        if ($type instanceof FullyQualified) {
            return new ClassType($type->toString(), null);
        }

        if ($type instanceof Name) {
            $resolved = $resolver->resolve($type->toString());
            if (!$resolved) {
                throw new RuntimeException(sprintf('Unable to resolve class "%s"', $type->toString()));
            }

            return new ClassType($resolved, $type->toString());
        }

        throw new RuntimeException(sprintf('Unhandled declared type "%s"', get_class($type)));
    }
}
