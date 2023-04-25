<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\ClassPropertyTypeFinder;
use RuntimeException;

class ClassScope implements ResolverContract
{
    private string $className;

    /**
     * @var array<MethodScope|VirtualMethodScope>
     */
    private array $methods = [];

    /**
     * @var array<string, string[]>
     */
    private array $properties = [];

    public function __construct(
        public readonly FileScope $scope,
        private readonly ClassPropertyTypeFinder $typeFinder,
        private readonly ?ClassScope $extends = null,
    ) {
        //
    }

    public static function create(FileScope $scope): self
    {
        return resolve(self::class, ['scope' => $scope]);
    }

    /**
     * @param string $name
     * @param string[] $types
     * @return $this
     */
    public function addProperty(string $name, array $types): self
    {
        $this->properties[$name] = $types;

        return $this;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function extend(FileScope $scope): self
    {
        return new self(
            $scope,
            $this->typeFinder,
            $this,
        );
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedClassName(): string
    {
        /**
         * @var class-string $classString
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $classString = $this->scope->namespace() . '\\' . $this->className;

        return $classString;
    }

    public function method(string $methodName): MethodScope|VirtualMethodScope|null
    {
        $methodScope = collect($this->methods)
            ->first(fn(MethodScope|VirtualMethodScope $method) => $method->name() === $methodName);

        return $methodScope ?? VirtualMethodScope::create($this, $methodName, null);
    }

    /**
     * @throws ParseResultException
     */
    public function resolveClass(Name $name): string
    {
        try {
            return $this->scope->resolveClass($name);
        } catch (ParseResultException $exception) {
            if ($this->extends) {
                return $this->extends->resolveClass($name);
            }

            throw $exception;
        }
    }

    /**
     * @param string $name
     * @return string[]|null
     * @throws ReflectionException
     */
    public function propertyTypes(string $name): array|null
    {
        return $this->properties[$name]
            ?? $this->extends?->propertyTypes($name)
            ?? $this->typeFinder->find($this, $name);
    }

    /**
     * @return string[]
     */
    public function resolveVariable(string $variable): array
    {
        throw new RuntimeException('Cannot resolve variable in class scope');
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function setMethod(MethodScope|VirtualMethodScope $methodScope): self
    {
        $this->methods[$methodScope->name()] = $methodScope;

        return $this;
    }
}
