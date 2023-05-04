<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use ReflectionMethod;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;

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

    public function returnType(): TypeContract
    {
        return $this->typeConverter->convert($this->reflection->getReturnType());
    }
}
