<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use RuntimeException;
use Throwable;

/**
 * This converts a resource's TypeContract into a parser's ParserTypeContract.
 */
class ParserTypeConverter implements ParserTypeConverterContract
{
    public function convert(TypeContract $type): ParserTypeContract
    {
        try {
            return $this->convertType($type);
        } catch (Throwable $exception) {
            return (new Types\Zod\ZodUnknownType())
                ->setComment(sprintf('Error: %s', $exception->getMessage()));
        }
    }

    protected function convertCollection(Types\ClassType $type): ParserTypeContract
    {
        if ($type->generics()) {
            $values = $type->generics()->values();
            if ($values->count() > 1) {
                $key = $values->get(0);
                $value = $values->get(1);
            } else {
                $key = null;
                $value = $values->get(0);
            }

            if ($key instanceof Types\IntType) {
                $key = null;
            }

            return $this->convert(new Types\ArrayType($key, $value));
        }

        return (new Types\Zod\ZodUnknownType())
            ->setComment('Unknown generics on Collection');
    }

    protected function convertType(TypeContract $type): ParserTypeContract
    {
        if ($type instanceof Types\ArrayType) {
            return new Types\Zod\ZodArrayType(
                $type->keys ? $this->convert($type->keys) : null,
                $type->values ? $this->convert($type->values) : null,
            );
        }

        if ($type instanceof Types\ArrayWithPropertiesType) {
            return new Types\Zod\ZodShapeType($type->properties()->mapWithKeys(fn(TypeContract $type, string $key) => [
                $key => $this->convert($type),
            ])->sort());
        }

        if ($type instanceof Types\BoolType) {
            return new Types\Zod\ZodBooleanType();
        }

        if ($type instanceof Types\ClassWithMethodType) {
            return new Types\Zod\ZodShapeReferenceType($type->fullyQualifiedName(), $type->methodName());
        }

        if ($type instanceof Types\ClassType && $type->fullyQualifiedName() === Collection::class) {
            return $this->convertCollection($type);
        }

        if ($type instanceof Types\EmptyArrayType) {
            return new Types\Zod\ZodArrayType(null, new Types\Zod\ZodNeverType());
        }

        if ($type instanceof Types\ErrorType) {
            return (new Types\Zod\ZodUnknownType())
                ->setComment(sprintf('Error: %s', $type->exception->getMessage()));
        }

        if ($type instanceof Types\FloatType) {
            return new Types\Zod\ZodNumberType();
        }

        if ($type instanceof Types\IntType) {
            return new Types\Zod\ZodNumberType();
        }

        if ($type instanceof Types\IntersectionType) {
            return new Types\Zod\ZodIntersectionType(
                ...$type->types()->map(fn(TypeContract $type) => $this->convert($type))->all(),
            );
        }

        if ($type instanceof Types\MixedType) {
            return new Types\Zod\ZodUnknownType();
        }

        if ($type instanceof Types\NullType) {
            return new Types\Zod\ZodNullType();
        }

        if ($type instanceof Types\StringType) {
            return new Types\Zod\ZodStringType();
        }

        if ($type instanceof Types\UndefinedType) {
            return new Types\Zod\ZodUndefinedType();
        }

        if ($type instanceof Types\UntypedType && $type->comment()) {
            return (new Types\Zod\ZodUnknownType())
                ->setComment($type->comment());
        }

        if ($type instanceof Types\UnionType) {
            return $this->convertUnion($type);
        }

        if ($type instanceof Types\VoidType) {
            return new Types\Zod\ZodNullType();
        }

        if ($type instanceof Types\ClassType) {
            throw new RuntimeException(sprintf(
                '%s of "%s" cannot be converted to parser type.',
                class_basename($type),
                $type->fullyQualifiedName(),
            ));
        }

        throw new RuntimeException(sprintf(
            '%s cannot be converted to parser type.',
            class_basename($type),
        ));
    }

    protected function convertUnion(Types\UnionType $type): ParserTypeContract
    {
        $subTypes = $type->types();
        if ($subTypes->count() === 2) {
            if ($type->hasType(Types\NullType::class)) {
                return new Types\Zod\ZodNullableType($this->convert(
                    $subTypes
                        ->filter(fn(TypeContract $type) => !($type instanceof Types\NullType))
                        ->firstOrFail(),
                ));
            }

            if ($type->hasType(Types\UndefinedType::class)) {
                return new Types\Zod\ZodOptionalType($this->convert(
                    $subTypes
                        ->filter(fn(TypeContract $type) => !($type instanceof Types\UndefinedType))
                        ->firstOrFail(),
                ));
            }
        }

        return new Types\Zod\ZodUnionType(...$subTypes->map(fn(TypeContract $type) => $this->convert($type))->all());
    }
}
