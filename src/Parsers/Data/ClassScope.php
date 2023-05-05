<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\ClassLike;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Parsers\DocBlockParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class ClassScope implements ClassScopeContract
{
    private DocBlock|null $docBlock = null;

    /**
     * @var Collection<string, ClassMethodScope>
     */
    private readonly Collection $methods;

    /**
     * @var Collection<string, ClassProperty>
     */
    private readonly Collection $properties;

    /**
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param array<int, ClassScopeContract> $traits
     * @param DocBlockParser $docBlockParser
     */
    public function __construct(
        private readonly ClassLike $node,
        private readonly ResolverContract $resolver,
        private readonly ClassScopeContract|null $extends,
        private readonly array $traits,
        private readonly DocBlockParser $docBlockParser,
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
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        ClassScopeContract ...$traits,
    ): self {
        return resolve(self::class, [
            'node' => $node,
            'resolver' => $resolver,
            'extends' => $extends,
            'traits' => $traits,
        ]);
    }

    public function docBlock(): DocBlock|null
    {
        if ($this->docBlock === null && $this->node->getDocComment() !== null) {
            $this->docBlock = $this->docBlockParser->parse(
                $this->node->getDocComment()->getText(),
                $this->resolver,
            );
        }

        return $this->docBlock;
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

    public function method(string $name): ClassMethodScopeContract|null
    {
        if ($this->docBlock()?->hasMethod($name)) {
            return VirtualClassMethodScope::create($this->docBlock()->method($name));
        }

        $method = $this->methods->get($name);

        if ($method === null && $this->extends()) {
            $method = $this->extends()->method($name);
        }

        if ($method === null && count($this->traits)) {
            foreach ($this->traits as $trait) {
                $method = $trait->method($name);

                if ($method !== null) {
                    break;
                }
            }
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

    public function property(string $name): ClassPropertyContract|null
    {
        if ($this->docBlock()?->hasProperty($name)) {
            return VirtualClassProperty::create($this->docBlock()->property($name));
        }

        $property = $this->properties->get($name);

        if ($property === null && $this->extends()) {
            $property = $this->extends()->property($name);
        }

        return $property;
    }

    public function propertyType(string $name): TypeContract|null
    {
        return $this->property($name)?->type();
    }
}