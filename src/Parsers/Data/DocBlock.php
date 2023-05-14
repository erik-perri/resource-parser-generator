<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class DocBlock
{
    /**
     * @param TypeContract|null $return
     * @param Collection<string, TypeContract> $params
     * @param Collection<string, TypeContract> $properties
     * @param Collection<string, TypeContract> $methods
     * @param Collection<string, TypeContract> $vars
     */
    public function __construct(
        private TypeContract|null $return,
        private readonly Collection $params,
        private readonly Collection $properties,
        private readonly Collection $methods,
        private readonly Collection $vars,
    ) {
        //
    }

    public static function create(
        TypeContract|null $return = null
    ): self {
        return resolve(self::class, [
            'return' => $return,
            'params' => collect(),
            'properties' => collect(),
            'methods' => collect(),
            'vars' => collect(),
        ]);
    }

    public function hasMethod(string $name): bool
    {
        return $this->methods->has($name);
    }

    public function hasParam(string $name): bool
    {
        return $this->params->has($name);
    }

    public function hasProperty(string $name): bool
    {
        return $this->properties->has($name);
    }

    public function hasVar(string $name): bool
    {
        return $this->vars->has($name);
    }

    public function method(string $name): TypeContract
    {
        if (!isset($this->methods[$name])) {
            throw new InvalidArgumentException('"@method ' . $name . '" is not defined in docblock');
        }

        return $this->methods[$name];
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function methods(): Collection
    {
        return $this->methods->collect();
    }

    public function param(string $name): TypeContract
    {
        if (!isset($this->params[$name])) {
            throw new InvalidArgumentException('"@param ' . $name . '" is not defined in docblock');
        }

        return $this->params[$name];
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function params(): Collection
    {
        return $this->params->collect();
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }

    public function property(string $name): TypeContract
    {
        if (!isset($this->properties[$name])) {
            throw new InvalidArgumentException('"@property ' . $name . '" is not defined in docblock');
        }

        return $this->properties[$name];
    }

    public function return(): TypeContract|null
    {
        return $this->return;
    }

    public function var(string $string): TypeContract
    {
        if (!isset($this->vars[$string])) {
            throw new InvalidArgumentException('"@var ' . $string . '" is not defined in docblock');
        }

        return $this->vars[$string];
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function vars(): Collection
    {
        return $this->vars->collect();
    }

    public function setMethod(string $name, TypeContract $returnType): self
    {
        $this->methods->put($name, $returnType);

        return $this;
    }

    public function setParam(string $name, TypeContract $type): self
    {
        $this->params->put($name, $type);

        return $this;
    }

    public function setProperty(string $name, TypeContract $type): self
    {
        $this->properties->put($name, $type);

        return $this;
    }

    public function setReturn(TypeContract $returnType): self
    {
        $this->return = $returnType;

        return $this;
    }

    public function setVar(string $name, TypeContract $type): self
    {
        $this->vars->put($name, $type);

        return $this;
    }
}
