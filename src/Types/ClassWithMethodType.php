<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Types\Zod\ZodShapeReferenceType;

class ClassWithMethodType extends ClassType
{
    /**
     * @param class-string $fullyQualifiedName
     * @param string|null $alias
     * @param string $methodName
     */
    public function __construct(
        string $fullyQualifiedName,
        string|null $alias,
        private readonly string $methodName,
    ) {
        parent::__construct($fullyQualifiedName, $alias);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        return $this->fullyQualifiedName() . '::' . $this->methodName();
    }

    public function methodName(): string
    {
        return $this->methodName;
    }

    public function parserType(): ParserTypeContract
    {
        return ZodShapeReferenceType::create($this->fullyQualifiedName(), $this->methodName());
    }
}
