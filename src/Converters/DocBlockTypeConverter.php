<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ResourceParserGenerator\Contracts\Converters\DocBlockTypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

/**
 * This class takes a parsed docblock type and converts it to a TypeContract.
 *
 * `@param string $foo ` `string` is a docblock type.
 */
class DocBlockTypeConverter implements DocBlockTypeConverterContract
{
    public function convert(TypeNode $type, ResolverContract $resolver): TypeContract
    {
        if ($type instanceof UnionTypeNode) {
            return new Types\UnionType(
                ...array_map(fn(TypeNode $type) => $this->convert($type, $resolver), $type->types),
            );
        }

        if ($type instanceof NullableTypeNode) {
            return new Types\UnionType($this->convert($type->type, $resolver), new Types\NullType());
        }

        if ($type instanceof CallableTypeNode) {
            return new Types\CallableType();
        }

        if ($type instanceof GenericTypeNode) {
            $containerType = $this->convert($type->type, $resolver);
            if ($containerType instanceof Types\ArrayType) {
                if (count($type->genericTypes) === 1) {
                    return new Types\ArrayType(
                        null,
                        $this->convert($type->genericTypes[0], $resolver),
                    );
                } elseif (count($type->genericTypes) === 2) {
                    return new Types\ArrayType(
                        $this->convert($type->genericTypes[0], $resolver),
                        $this->convert($type->genericTypes[1], $resolver),
                    );
                }
            }

            if ($containerType instanceof Types\ClassType) {
                return new Types\ClassType(
                    $containerType->fullyQualifiedName(),
                    $containerType->alias(),
                    collect($type->genericTypes)->map(fn(TypeNode $type) => $this->convert($type, $resolver)),
                );
            }

            throw new RuntimeException(sprintf('Unhandled generic type for "%s"', $containerType->describe()));
        }

        if ($type instanceof ArrayTypeNode) {
            return new Types\ArrayType(
                null,
                $this->convert($type->type, $resolver),
            );
        }

        if ($type instanceof ThisTypeNode) {
            $thisType = $resolver->resolveThis();
            if (!$thisType) {
                throw new RuntimeException('Cannot resolve $this type');
            }

            return new Types\ClassType($thisType, null);
        }

        if ($type instanceof IdentifierTypeNode) {
            switch ($type->name) {
                case 'array':
                    return new Types\ArrayType(null, null);
                case 'array-key':
                    return new Types\UnionType(new Types\IntType(), new Types\StringType());
                case 'bool':
                case 'false':
                case 'true':
                    return new Types\BoolType();
                case 'callable':
                    return new Types\CallableType();
                case 'float':
                    return new Types\FloatType();
                case 'int':
                    return new Types\IntType();
                case 'mixed':
                    return new Types\MixedType();
                case 'null':
                    return new Types\NullType();
                case 'object':
                    return new Types\ObjectType();
                case 'resource':
                    return new Types\ResourceType();
                case 'string':
                    return new Types\StringType();
                case 'void':
                    return new Types\VoidType();
                case 'self':
                case 'static':
                    $thisType = $resolver->resolveThis();
                    if (!$thisType) {
                        throw new RuntimeException('Cannot resolve static type');
                    }
                    return new Types\ClassType($thisType, null);
                default:
                    break;
            }

            if (str_starts_with($type->name, '\\')) {
                /**
                 * @var class-string $className
                 */
                $className = ltrim($type->name, '\\');

                return new Types\ClassType($className, null);
            } else {
                $className = $resolver->resolveClass($type->name);
                if (!$className) {
                    throw new RuntimeException(sprintf('Could not resolve class name "%s"', $type->name));
                }

                return new Types\ClassType($className, $type->name);
            }
        }

        throw new RuntimeException(sprintf('Unhandled type "%s"', get_class($type)));
    }
}
