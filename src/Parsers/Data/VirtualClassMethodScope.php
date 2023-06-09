<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ResourceParserGenerator\Contracts\AttributeContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use RuntimeException;

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

    public function attribute(string $className): AttributeContract|null
    {
        throw new RuntimeException('Cannot read attributes on virtual class method');
    }

    public function returnType(): TypeContract
    {
        return $this->returnType;
    }
}
