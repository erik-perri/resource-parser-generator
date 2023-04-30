<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class DocBlockTypeParser
{
    public function parse(TypeNode $type, ResolverContract $resolver): TypeContract
    {
        if ($type instanceof UnionTypeNode) {
            return new Types\UnionType(
                ...array_map(fn(TypeNode $type) => $this->parse($type, $resolver), $type->types),
            );
        }

        if ($type instanceof GenericTypeNode) {
            $containerType = $this->parse($type->type, $resolver);
            if ($containerType instanceof Types\ArrayType) {
                if (count($type->genericTypes) === 1) {
                    return new Types\ArrayType(
                        null,
                        $this->parse($type->genericTypes[0], $resolver),
                    );
                } elseif (count($type->genericTypes) === 2) {
                    return new Types\ArrayType(
                        $this->parse($type->genericTypes[0], $resolver),
                        $this->parse($type->genericTypes[1], $resolver),
                    );
                }
            }

            // TODO Generic sub-types?
            return $this->parse($type->type, $resolver);
        }

        if ($type instanceof ArrayTypeNode) {
            return new Types\ArrayType(
                null,
                $this->parse($type->type, $resolver),
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
                case 'bool':
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
