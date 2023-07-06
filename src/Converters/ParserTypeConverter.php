<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;
use Throwable;

class ParserTypeConverter implements ParserTypeConverterContract
{
    public function convert(TypeContract $type): ParserTypeContract
    {
        try {
            if ($type instanceof Types\ClassType && $type->fullyQualifiedName() === Collection::class) {
                return $this->convertCollection($type);
            }
            return $type->parserType();
        } catch (Throwable $exception) {
            return (new Types\Zod\ZodUnknownType())
                ->setComment(sprintf('Error: %s', $exception->getMessage()));
        }
    }

    private function convertCollection(Types\ClassType $type): ParserTypeContract
    {
        if ($type->generics()) {
            if ($type->generics()->count() > 1) {
                $key = $type->generics()->get(0);
                $value = $type->generics()->get(1);
            } else {
                $key = null;
                $value = $type->generics()->get(0);
            }

            if ($key instanceof Types\IntType) {
                $key = null;
            }

            return (new Types\ArrayType($key, $value))->parserType();
        }

        return (new Types\Zod\ZodUnknownType())
            ->setComment('Unknown generics on Collection');
    }
}
