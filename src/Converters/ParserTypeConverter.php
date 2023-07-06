<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

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
            return $type->parserType();
        } catch (Throwable $exception) {
            return (new Types\Zod\ZodUnknownType())
                ->setComment(sprintf('Error: %s', $exception->getMessage()));
        }
    }
}
