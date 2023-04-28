<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Scopes;

class ClassScope
{
    public function __construct(
        public readonly FileScope $file,
        public readonly string $name,
    ) {
        //
    }

    public static function create(FileScope $file, string $name): self
    {
        return resolve(self::class, [
            'file' => $file,
            'name' => $name,
        ]);
    }
}
