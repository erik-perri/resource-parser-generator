<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\ResolverContract;
use RuntimeException;

class ClassScope implements ClassScopeContract
{
    /**
     * @var Collection<string, ClassMethodScope>
     */
    private readonly Collection $methods;

    /**
     * @var Collection<string, ClassProperty>
     */
    private readonly Collection $properties;

    public function __construct(
        private readonly Class_ $node,
        private readonly ClassScope|null $extends,
        private readonly ResolverContract $resolver,
    ) {
        $this->methods = collect();
        $this->properties = collect();

        foreach ($this->node->getMethods() as $method) {
            $methodScope = ClassMethodScope::create($method, $this->resolver);
            $this->methods->put($methodScope->name(), $methodScope);
        }

        foreach ($this->node->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $propertyScope = ClassProperty::create($property, $prop, $this->resolver);
                $this->properties->put($propertyScope->name(), $propertyScope);
            }
        }
    }

    public static function create(
        Class_ $node,
        ClassScope|null $extends,
        ResolverContract $resolver,
    ): self {
        return resolve(self::class, [
            'node' => $node,
            'extends' => $extends,
            'resolver' => $resolver,
        ]);
    }

    public function extends(): ClassScopeContract|null
    {
        return $this->extends;
    }

    public function name(): string
    {
        return $this->node->name
            ? $this->node->name->toString()
            : sprintf('AnonymousClass%d', $this->node->getLine());
    }

    /**
     * @return Collection<string, ClassMethodScope>
     */
    public function methods(): Collection
    {
        return $this->methods->collect();
    }

    public function method(string $name): ClassMethodScopeContract
    {
        $method = $this->methods->get($name);

        if ($method === null && $this->extends()) {
            $method = $this->extends()->method($name);
        }

        if ($method === null) {
            throw new RuntimeException(sprintf('Method "%s" not found', $name));
        }

        return $method;
    }

    /**
     * @return Collection<string, ClassProperty>
     */
    public function properties(): Collection
    {
        return $this->properties->collect();
    }

    public function property(string $name): ClassPropertyContract
    {
        $property = $this->properties->get($name);

        if ($property === null && $this->extends()) {
            $property = $this->extends()->property($name);
        }

        if ($property === null) {
            throw new RuntimeException(sprintf('Property "%s" not found', $name));
        }

        return $property;
    }
}
