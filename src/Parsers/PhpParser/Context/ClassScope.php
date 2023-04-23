<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;
use ResourceParserGenerator\Exceptions\ParseResultException;
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

    public function __construct(public readonly FileScope $scope)
    {
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
        return $this->scope->resolveClass($name);
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

    /**
     * @param string $name
     * @return string[]|null
     */
    public function propertyTypes(string $name): array|null
    {
        return $this->properties[$name] ?? null;
    }
}
