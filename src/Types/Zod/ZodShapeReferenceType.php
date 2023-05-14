<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Generators\Contracts\ParserNameGeneratorContract;

class ZodShapeReferenceType implements ParserTypeContract
{
    public function __construct(
        private readonly string $fullyQualifiedResourceName,
        private readonly string $methodName,
        private readonly ParserNameGeneratorContract $parserNameGenerator,
    ) {
        //
    }

    public static function create(
        string $fullyQualifiedResourceName,
        string $methodName,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedResourceName' => $fullyQualifiedResourceName,
            'methodName' => $methodName,
        ]);
    }

    public function imports(): array
    {
        return [];
    }

    public function constraint(): string
    {
        return $this->parserNameGenerator->generateVariableName($this->fullyQualifiedResourceName, $this->methodName);
    }
}
