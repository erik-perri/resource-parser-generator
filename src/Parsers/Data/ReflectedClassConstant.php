<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ReflectedClassConstant implements ClassConstantContract
{
    public function __construct(
        private readonly TypeContract $type,
        private readonly mixed $value,
    ) {
        //
    }

    public static function create(TypeContract $type, mixed $value): self
    {
        return resolve(self::class, [
            'type' => $type,
            'value' => $value,
        ]);
    }

    public function type(): TypeContract
    {
        return $this->type;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
