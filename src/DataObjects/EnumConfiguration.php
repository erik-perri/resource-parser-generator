<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use ResourceParserGenerator\Contracts\DataObjects\EnumSourceContract;

class EnumConfiguration implements EnumSourceContract
{
    /**
     * @param class-string $className
     * @param string|null $enumFile
     * @param string|null $typeName
     */
    public function __construct(
        public readonly string $className,
        public readonly ?string $enumFile = null,
        public readonly ?string $typeName = null,
    ) {
        //
    }

    /**
     * TODO Go back to pure array config to avoid this?
     *
     * @param array{
     *     className: class-string,
     *     enumFile: string,
     *     typeName: string,
     * } $data
     * @return self
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['className'],
            $data['enumFile'],
            $data['typeName'],
        );
    }
}
