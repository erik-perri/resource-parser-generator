<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use ReflectionClass;
use ReflectionException;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\TypeContract;

class ReflectedClassScope implements ClassScopeContract
{
    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     */
    public function __construct(
        private readonly ReflectionClass $reflection,
    ) {
        //
    }

    /**
     * @throws ReflectionException
     */
    public function method(string $name): ClassMethodScopeContract|null
    {
        if (!$this->reflection->hasMethod($name)) {
            return null;
        }
        return ReflectedClassMethodScope::create($this->reflection->getMethod($name));
    }

    public function name(): string
    {
        return $this->reflection->getName();
    }

    /**
     * @throws ReflectionException
     */
    public function property(string $name): ClassPropertyContract|null
    {
        if (!$this->reflection->hasProperty($name)) {
            return null;
        }
        return ReflectedClassProperty::create($this->reflection->getProperty($name));
    }

    /**
     * @throws ReflectionException
     */
    public function propertyType(string $name): TypeContract|null
    {
        return $this->property($name)?->type();
    }
}
