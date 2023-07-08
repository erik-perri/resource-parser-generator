<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;

class ResourcePath implements ParserSourceContract
{
    public function __construct(
        public readonly string $path,
        public readonly string $fileMatch = '/\.php$/i',
    ) {
        //
    }

    /**
     * TODO Go back to pure array config to avoid this?
     *
     * @param array{path: string, fileMatch: string} $data
     * @return self
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['path'],
            $data['fileMatch'],
        );
    }
}
