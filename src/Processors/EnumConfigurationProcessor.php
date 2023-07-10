<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Processors;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Types\TypeWithChildrenContract;
use ResourceParserGenerator\DataObjects\EnumData;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\Generators\EnumConfigurationGenerator;
use ResourceParserGenerator\Types\EnumType;
use RuntimeException;
use Throwable;

class EnumConfigurationProcessor
{
    public function __construct(
        private readonly EnumConfigurationGenerator $configurationGenerator,
    ) {
        //
    }

    /**
     * @param ParserGeneratorConfiguration $configuration
     * @param Collection<int, ResourceData> $resources
     * @return Collection<int, EnumData>
     */
    public function process(ParserGeneratorConfiguration $configuration, Collection $resources): Collection
    {
        $enums = collect();

        foreach ($resources as $resource) {
            foreach ($resource->properties as $name => $property) {
                try {
                    $types = $property instanceof TypeWithChildrenContract
                        ? $property->children()->add($property)
                        : collect([$property]);

                    foreach ($types as $type) {
                        if (!($type instanceof EnumType)) {
                            continue;
                        }

                        $enums = $enums->add(new EnumData(
                            $this->configurationGenerator->generate($configuration, $type->fullyQualifiedName),
                            collect(), // TODO $this->...->parse(...);
                        ));
                    }
                } catch (Throwable $error) {
                    throw new RuntimeException(sprintf(
                        'Failed to pars enum "%s" for "%s::%s"',
                        $name,
                        $resource->className,
                        $resource->methodName,
                    ), 0, $error);
                }
            }
        }

        return $enums;
    }
}
