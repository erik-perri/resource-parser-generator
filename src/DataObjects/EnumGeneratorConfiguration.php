<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class EnumGeneratorConfiguration
{
    /**
     * @var ReadOnlyCollection<int, EnumConfiguration>
     */
    public readonly ReadOnlyCollection $enums;

    public function __construct(
        public readonly string $outputPath,
        EnumConfiguration ...$enums,
    ) {
        $this->enums = new ReadOnlyCollection(array_values($enums));
    }

    /**
     * @param class-string $className
     * @return EnumConfiguration|null
     */
    public function enum(string $className): ?EnumConfiguration
    {
        return $this->enums->first(
            fn(EnumConfiguration $configuration) => $configuration->className === $className,
        );
    }
}
