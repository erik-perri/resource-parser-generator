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
use ResourceParserGenerator\Types;
use RuntimeException;

class DeclaredTypeParser
{
    public function parse(ComplexType|Identifier|Name|null $type, ClassNameResolverContract $resolver): TypeContract
    {
        if (!$type) {
            return new Types\UntypedType();
        }

        if ($type instanceof Identifier) {
            return match ($type->name) {
                'array' => new Types\ArrayType(null),
                'bool' => new Types\BoolType(),
                'float' => new Types\FloatType(),
                'int' => new Types\IntType(),
                'mixed' => new Types\MixedType(),
                'null' => new Types\NullType(),
                'object' => new Types\ObjectType(),
                'string' => new Types\StringType(),
                'void' => new Types\VoidType(),
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
                new Types\NullType(),
                $this->parse($type->type, $resolver),
            );
        }

        if ($type instanceof FullyQualified) {
            return new Types\ClassType($type->toString(), null);
        }

        if ($type instanceof Name) {
            $resolved = $resolver->resolve($type->toString());
            if (!$resolved) {
                throw new RuntimeException(sprintf('Unable to resolve class "%s"', $type->toString()));
            }

            return new Types\ClassType($resolved, $type->toString());
        }

        throw new RuntimeException(sprintf('Unhandled declared type "%s"', get_class($type)));
    }
}
