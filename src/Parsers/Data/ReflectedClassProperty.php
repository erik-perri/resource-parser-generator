<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ReflectionProperty;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;

class ReflectedClassProperty implements ClassPropertyContract
{
    public function __construct(
        private readonly ReflectionProperty $reflection,
        private readonly ReflectionTypeConverter $typeConverter,
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
