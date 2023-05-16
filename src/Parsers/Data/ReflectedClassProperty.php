<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ReflectionProperty;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\Converters\ReflectionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class ReflectedClassProperty implements ClassPropertyContract
{
    public function __construct(
        private readonly ReflectionProperty $reflection,
        private readonly ReflectionTypeConverterContract $typeConverter,
    ) {
        //
    }

    public static function create(ReflectionProperty $reflection): self
    {
        return resolve(self::class, [
            'reflection' => $reflection,
        ]);
    }

    public function type(): TypeContract
    {
        return $this->typeConverter->convert($this->reflection->getType());
    }
}
