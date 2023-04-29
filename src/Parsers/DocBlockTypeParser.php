<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class DocBlockTypeParser
{
    public function parse(TypeNode $type, ClassNameResolverContract $resolver): TypeContract
    {
        if ($type instanceof UnionTypeNode) {
            return new Types\UnionType(
                ...array_map(fn(TypeNode $type) => $this->parse($type, $resolver), $type->types),
            );
        }

        if ($type instanceof IdentifierTypeNode) {
            switch ($type->name) {
                case 'array':
                    return new Types\ArrayType(null);
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
                return new Types\ClassType(ltrim($type->name, '\\'), null);
            } else {
                $className = $resolver->resolve($type->name);
                if (!$className) {
                    throw new RuntimeException(sprintf('Could not resolve class name "%s"', $type->name));
                }

                return new Types\ClassType($className, $type->name);
            }
        }

        throw new RuntimeException(sprintf('Unhandled type "%s"', get_class($type)));
    }
}
