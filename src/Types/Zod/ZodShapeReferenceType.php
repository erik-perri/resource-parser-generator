<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Parsers\Data\ResourceParserData;

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

    public function constraint(): string
    {
        return $this->parserNameGenerator->generateVariableName($this->fullyQualifiedResourceName, $this->methodName);
    }

    public function imports(): array
    {
        return [];
    }

    /**
     * @param Collection<int, ResourceParserData> $parsers
     * @return array<string, string[]>
     */
    public function shapeImport(Collection $parsers): array
    {
        $alreadyParsedResource = $parsers->first(function (ResourceParserData $parser) {
            return $parser->fullyQualifiedResourceName() === $this->fullyQualifiedResourceName;
        });

        if (!$alreadyParsedResource) {
            $fileName = $this->parserNameGenerator->generateFileName($this->fullyQualifiedResourceName);
            $parserName = $this->parserNameGenerator->generateVariableName(
                $this->fullyQualifiedResourceName,
                $this->methodName,
            );
            return [
                // TODO Generate this path somehow. Currently it works since we don't support any other structure than
                //      all parsers in the same location.
                './' . $fileName => [$parserName],
            ];
        }

        return [];
    }
}
