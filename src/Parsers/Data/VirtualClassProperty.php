<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class VirtualClassProperty implements ClassPropertyContract
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
