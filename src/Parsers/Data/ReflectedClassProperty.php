<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ReflectionProperty;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;
use ResourceParserGenerator\Types\Contracts\TypeContract;

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
