<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\VariableTypeConverter;

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

    public function hasParent(string $className): bool
    {
        $parent = $this->reflection;
        while ($parent = $parent->getParentClass()) {
            if ($parent->name === $className) {
                return true;
            }
        }

        return false;
    }

    public function methods(): Collection
    {
        /**
         * @var Collection<string, ClassMethodScopeContract>
         */
        return collect($this->reflection->getMethods())
            ->map(fn(ReflectionMethod $method) => ReflectedClassMethodScope::create($method));
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

    public function parent(): ClassScopeContract|null
    {
        return $this->reflection->getParentClass()
            ? ReflectedClassScope::create($this->reflection->getParentClass())
            : null;
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

        $constant = $this->reflection->getConstant($name);
        $type = $this->variableTypeConverter->convert($constant);

        return ReflectedClassConstant::create($type, $constant);
    }
}
