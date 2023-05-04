<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\TypeContract;

class VirtualClassMethodScope implements ClassMethodScopeContract
{
    public function __construct(
        private readonly TypeContract $returnType,
    ) {
        //
    }

    public static function create(TypeContract $returnType): self
    {
        return resolve(self::class, [
            'returnType' => $returnType,
        ]);
    }

    public function returnType(): TypeContract
    {
        return $this->returnType;
    }
}
