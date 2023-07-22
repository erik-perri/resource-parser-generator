<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Processors;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\EnumData;
use ResourceParserGenerator\DataObjects\ParserData;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Generators\ParserConfigurationGenerator;
use RuntimeException;
use Throwable;

class ParserConfigurationProcessor
{
    public function __construct(
        private readonly ParserConfigurationGenerator $parserConfigurationGenerator,
    ) {
        //
    }

    /**
     * Convert the resource types into parser types and return a collection of the data for the parsers.
     *
     * @param ParserGeneratorConfiguration $configuration
     * @param Collection<int, ResourceData> $resources
     * @param Collection<int, EnumData> $enums
     * @return Collection<int, ParserData>
     */
    public function process(
        ParserGeneratorConfiguration $configuration,
        Collection $resources,
        Collection $enums,
    ): Collection {
        $parsers = collect();

        /** @var ParserTypeConverterContract $parserTypeConverter */
        $parserTypeConverter = resolve(ParserTypeConverterContract::class, [
            'enums' => $enums,
        ]);

        foreach ($resources as $resource) {
            try {
                $parsers = $parsers->add(new ParserData(
                    $resource,
                    $this->parserConfigurationGenerator
                        ->generate($configuration, $resource->className, $resource->methodName),
                    $resource->properties
                        ->map(fn(TypeContract $type) => $parserTypeConverter->convert($type)),
                ));
            } catch (Throwable $error) {
                throw new RuntimeException(sprintf(
                    'Failed to create parser for "%s::%s"',
                    $resource->className,
                    $resource->methodName,
                ), 0, $error);
            }
        }

        return $parsers;
    }
}
