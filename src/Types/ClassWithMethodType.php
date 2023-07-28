<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

class ClassWithMethodType extends ClassType
{
    /**
     * @param class-string $fullyQualifiedName
     * @param string|null $alias
     * @param string|null $methodName
     * @param bool $isCollection
     */
    public function __construct(
        string $fullyQualifiedName,
        string|null $alias,
        public readonly string|null $methodName,
        public readonly bool $isCollection,
    ) {
        parent::__construct($fullyQualifiedName, $alias);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        $description = $this->methodName
            ? sprintf('%s::%s', $this->fullyQualifiedName(), $this->methodName)
            : $this->fullyQualifiedName();

        return $this->isCollection
            ? sprintf('%s[]', $description)
            : sprintf('%s', $description);
    }
}
