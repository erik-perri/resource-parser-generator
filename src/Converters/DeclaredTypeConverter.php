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
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;

class DeclaredTypeConverter
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
                'resource' => new Types\ResourceType(),
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
            if ($type->toString() === 'static') {
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
