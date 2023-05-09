<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use ReflectionClass;
use ReflectionException;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Converters\VariableTypeConverter;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ReflectedClassScope implements ClassScopeContract
{
    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @param VariableTypeConverter $variableTypeConverter
     */
    public function __construct(
        private readonly ReflectionClass $reflection,
        private readonly VariableTypeConverter $variableTypeConverter,
    ) {
        //
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return ReflectedClassScope
     */
    public static function create(ReflectionClass $reflection): self
    {
        return resolve(self::class, [
            'reflection' => $reflection,
        ]);
    }

    public function fullyQualifiedName(): string
    {
        return $this->reflection->getName();
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
        return $this->reflection->getShortName();
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

    public function constant(string $name): ClassConstantContract|null
    {
        if (!$this->reflection->hasConstant($name)) {
            return null;
        }

        $type = $this->variableTypeConverter->convert($this->reflection->getConstant($name));

        return ReflectedClassConstant::create($type);
    }
}
