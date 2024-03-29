<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class takes a parsed explicitly declared type and converts it to a TypeContract.
 *
 * "fn(?string $foo): int => 1"
 *     |     |        | |
 *     ComplexType    Identifier
 *
 * "Resource::make(...)"
 *  |      |
 *  Name
 */
class DeclaredTypeConverter implements DeclaredTypeConverterContract
{
    public function convert(ComplexType|Identifier|Name|null $type, ResolverContract $resolver): TypeContract
    {
        if (!$type) {
            return new Types\UntypedType();
        }

        if ($type instanceof Identifier) {
            return match ($type->name) {
                'array' => new Types\ArrayType(null, null),
                'bool' => new Types\BoolType(),
                'callable' => new Types\CallableType(),
                'float' => new Types\FloatType(),
                'int' => new Types\IntType(),
                'mixed' => new Types\MixedType(),
                'null' => new Types\NullType(),
                'object' => new Types\ObjectType(),
                'resource' => throw new RuntimeException('Resource type is not supported'),
                'string' => new Types\StringType(),
                'void' => new Types\VoidType(),
                default => throw new RuntimeException(sprintf('Unhandled identifier type "%s"', $type->name)),
            };
        }

        if ($type instanceof UnionType) {
            return new Types\UnionType(
                ...array_map(fn(ComplexType|Identifier|Name $type) => $this->convert($type, $resolver), $type->types),
            );
        }

        if ($type instanceof IntersectionType) {
            return new Types\IntersectionType(
                ...array_map(fn(ComplexType|Identifier|Name $type) => $this->convert($type, $resolver), $type->types),
            );
        }

        if ($type instanceof NullableType) {
            return new Types\UnionType(
                new Types\NullType(),
                $this->convert($type->type, $resolver),
            );
        }

        if ($type instanceof FullyQualified) {
            /**
             * @var class-string $className
             */
            $className = $type->toString();

            return new Types\ClassType($className, null);
        }

        if ($type instanceof Name) {
            if (in_array($type->toString(), ['self', 'static'], true)) {
                $resolved = $resolver->resolveThis();
            } else {
                $resolved = $resolver->resolveClass($type->toString());
            }
            if (!$resolved) {
                throw new RuntimeException(sprintf('Unable to resolve class "%s"', $type->toString()));
            }

            return new Types\ClassType($resolved, $type->toString());
        }

        throw new RuntimeException(sprintf('Unhandled declared type "%s"', get_class($type)));
    }
}
