<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ResourceParserGenerator\Types\ArrayWithPropertiesType;

class ParsedResource
{
    public function __construct(
        private readonly string $fullyQualifiedResourceName,
        private readonly string $format,
        private readonly ArrayWithPropertiesType $type,
    ) {
        //
    }

    public static function create(
        string $fullyQualifiedResourceName,
        string $format,
        ArrayWithPropertiesType $type,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedResourceName' => $fullyQualifiedResourceName,
            'format' => $format,
            'type' => $type,
        ]);
    }

    public function format(): string
    {
        return $this->format;
    }

    public function fullyQualifiedResourceName(): string
    {
        return $this->fullyQualifiedResourceName;
    }

    public function type(): ArrayWithPropertiesType
    {
        return $this->type;
    }
}
