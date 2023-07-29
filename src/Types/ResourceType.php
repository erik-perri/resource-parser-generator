<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\DataObjects\ResourceFormat;

class ResourceType extends ClassType
{
    /**
     * @param class-string $fullyQualifiedName
     * @param string|null $alias
     * @param ResourceFormat|null $format
     * @param bool $isCollection
     */
    public function __construct(
        string $fullyQualifiedName,
        string|null $alias,
        public readonly ResourceFormat|null $format,
        public readonly bool $isCollection,
    ) {
        parent::__construct($fullyQualifiedName, $alias);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        $description = $this->format
            ? sprintf('%s::%s', $this->fullyQualifiedName(), $this->format->methodName)
            : $this->fullyQualifiedName();

        return $this->isCollection
            ? sprintf('%s[]', $description)
            : sprintf('%s', $description);
    }
}
