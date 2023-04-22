<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders;

use ResourceParserGenerator\Builders\Constraints\ConstraintContract;

class ParserBuilder
{
    /**
     * @var array<string, ConstraintContract>
     */
    private array $properties = [];

    public function __construct(
        private readonly string $className,
        private readonly string $methodName,
    ) {
        //
    }

    public function addProperty(string $name, ConstraintContract $constraint): self
    {
        $this->properties[$name] = $constraint;

        return $this;
    }

    public function create(): string
    {
        return view('resource-parser-generator::resource-parser', [
            'properties' => array_map(
                fn(ConstraintContract $constraint) => $constraint->constraint(),
                $this->properties,
            ),
            'shortClassName' => class_basename($this->className),
            'methodName' => $this->methodName,
        ])->render();
    }

    /**
     * @return ConstraintContract[]
     */
    public function properties(): array
    {
        return $this->properties;
    }
}
