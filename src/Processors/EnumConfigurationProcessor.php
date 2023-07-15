<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Processors;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;
use ResourceParserGenerator\DataObjects\EnumConfiguration;
use ResourceParserGenerator\DataObjects\EnumData;
use ResourceParserGenerator\DataObjects\EnumGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Generators\EnumConfigurationGenerator;
use ResourceParserGenerator\Parsers\EnumCaseParser;
use ResourceParserGenerator\Types\EnumType;
use RuntimeException;
use Throwable;

class EnumConfigurationProcessor
{
    public function __construct(
        private readonly EnumConfigurationGenerator $configurationGenerator,
        private readonly EnumCaseParser $enumCaseParser,
    ) {
        //
    }

    /**
     * Parse any enums specified in the configuration or used in any resources and return a collection of the parsed
     * enums.
     *
     * @param EnumGeneratorConfiguration $configuration
     * @param Collection<int, ResourceData> $resources
     * @return Collection<int, EnumData>
     */
    public function process(EnumGeneratorConfiguration $configuration, Collection $resources): Collection
    {
        /**
         * @var Collection<int, array{class-string, null}> $enumClassesInConfiguration
         */
        $enumClassesInConfiguration = $configuration->enums->map(
            fn(EnumConfiguration $enum) => [$enum->className, null],
        );

        /**
         * @var Collection<int, array{class-string, string}> $enumClassesInResources
         */
        $enumClassesInResources = $this->getEnumClassesInResources($resources);

        /**
         * @var Collection<int, array{class-string, string|null}> $enumClassesInResources
         */
        $enumClasses = $enumClassesInConfiguration
            ->merge($enumClassesInResources)
            ->unique(fn(array $enum) => $enum[0]);

        return $enumClasses->map(function (array $data) use ($configuration) {
            /**
             * @var array{class-string, string|null} $data
             */
            try {
                return new EnumData(
                    $this->configurationGenerator->generate($configuration, $data[0]),
                    $this->enumCaseParser->parse($data[0]),
                );
            } catch (Throwable $error) {
                // TODO Don't throw and let type transformation fail at the Type->ParserType level instead?
                if ($data[1]) {
                    throw new RuntimeException(sprintf(
                        'Failed to parse enum "%s" for "%s"',
                        $data[0],
                        $data[1],
                    ), 0, $error);
                } else {
                    throw new RuntimeException(sprintf('Failed to parse enum "%s"', $data[0]), 0, $error);
                }
            }
        });
    }

    /**
     * @param Collection<int, ResourceData> $resources
     * @return Collection<int, array{class-string, string}>
     */
    private function getEnumClassesInResources(Collection $resources): Collection
    {
        $enums = collect();

        foreach ($resources as $resource) {
            foreach ($resource->properties as $name => $property) {
                $types = $property instanceof TypeWithChildrenContract
                    ? $property->children()->add($property)
                    : collect([$property]);

                foreach ($types as $type) {
                    if ($type instanceof EnumType) {
                        $enums = $enums->add([
                            $type->fullyQualifiedName,
                            sprintf('%s::%s', $resource->className, $resource->methodName),
                        ]);
                    }
                }
            }
        }

        return $enums;
    }
}
