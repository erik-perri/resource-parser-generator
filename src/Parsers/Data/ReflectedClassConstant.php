<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ReflectedClassConstant implements ClassConstantContract
{
    public function __construct(
        private readonly TypeContract $type,
    ) {
        //
    }

    public static function create(TypeContract $type): self
    {
        return resolve(self::class, [
            'type' => $type,
        ]);
    }

    public function type(): TypeContract
    {
        return $this->type;
    }
}
