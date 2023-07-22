<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

class ParserGeneratorConfiguration
{
    /**
     * @var ReadOnlyCollection<int, ParserConfiguration>
     */
    public readonly ReadOnlyCollection $parsers;

    public function __construct(
        public readonly ?string $outputPath,
        ParserConfiguration ...$parsers,
    ) {
        $this->parsers = new ReadOnlyCollection(array_values($parsers));
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ParserConfiguration|null
     */
    public function parser(string $className, string $methodName): ?ParserConfiguration
    {
        return $this->parsers->first(
            fn(ParserConfiguration $configuration) => $configuration->is($className, $methodName),
        );
    }
}
