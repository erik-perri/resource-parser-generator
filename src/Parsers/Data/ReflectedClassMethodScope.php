<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ReflectionMethod;
use ResourceParserGenerator\Contracts\AttributeContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;
use ResourceParserGenerator\Types\Contracts\TypeContract;
use RuntimeException;

class ReflectedClassMethodScope implements ClassMethodScopeContract
{
    public function __construct(
        private readonly ReflectionMethod $reflection,
        private readonly ReflectionTypeConverter $typeConverter,
    ) {
        //
    }

    public static function create(ReflectionMethod $reflection): self
    {
        return resolve(self::class, [
            'reflection' => $reflection,
        ]);
    }

    public function attribute(string $className): AttributeContract|null
    {
        throw new RuntimeException('Cannot read attributes on reflected class method');
    }

    public function returnType(): TypeContract
    {
        return $this->typeConverter->convert($this->reflection->getReturnType());
    }
}
