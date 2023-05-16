<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\ClassLike;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

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
     * @var Collection<string, ClassConstantContract>
     */
    private readonly Collection $constants;

    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param array<int, ClassScopeContract> $traits
     * @param DocBlockParserContract $docBlockParser
     */
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly ClassLike $node,
        private readonly ResolverContract $resolver,
        private readonly ClassScopeContract|null $extends,
        private readonly array $traits,
        private readonly DocBlockParserContract $docBlockParser,
    ) {
        $this->methods = collect();
        $this->properties = collect();
        $this->constants = collect();

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

        foreach ($this->node->getConstants() as $constantGroup) {
            foreach ($constantGroup->consts as $constant) {
                $constantScope = ClassConstant::create($constant, $this->resolver);
                $this->constants->put($constantScope->name(), $constantScope);
            }
        }
    }

    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param ClassScopeContract ...$traits
     * @return self
     */
    public static function create(
        string $fullyQualifiedName,
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        ClassScopeContract ...$traits,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedName' => $fullyQualifiedName,
            'node' => $node,
            'resolver' => $resolver,
            'extends' => $extends,
            'traits' => $traits,
        ]);
    }

    /**
     * @return Collection<string, ClassConstantContract>
     */
    public function constants(): Collection
    {
        return $this->constants->collect();
    }

    public function constant(string $name): ClassConstantContract|null
    {
        // TODO Docblock?

        $constant = $this->constants->get($name);

        if ($constant === null && $this->parent()) {
            $constant = $this->parent()->constant($name);
        }

        if ($constant === null) {
            foreach ($this->traits as $trait) {
                $constant = $trait->constant($name);

                if ($constant !== null) {
                    break;
                }
            }
        }

        return $constant;
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

    public function fullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    public function hasParent(string $className): bool
    {
        /**
         * @var ClassScopeContract|null $parent
         */
        $parent = $this;
        while ($parent = $parent?->parent()) {
            if ($parent->fullyQualifiedName() === $className) {
                return true;
            }
        }

        return false;
    }

    public function name(): string
    {
        return $this->node->name
            ? $this->node->name->toString()
            : sprintf('AnonymousClass%d', $this->node->getLine());
    }

    public function methods(): Collection
    {
        /**
         * @var Collection<string, ClassMethodScopeContract>
         */
        return $this->methods->collect();
    }

    public function method(string $name): ClassMethodScopeContract|null
    {
        if ($this->docBlock()?->hasMethod($name)) {
            return VirtualClassMethodScope::create($this->docBlock()->method($name));
        }

        $method = $this->methods->get($name);

        if ($method === null && $this->parent()) {
            $method = $this->parent()->method($name);
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

    public function parent(): ClassScopeContract|null
    {
        return $this->extends;
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

        if ($property === null && $this->parent()) {
            $property = $this->parent()->property($name);
        }

        return $property;
    }

    public function propertyType(string $name): TypeContract|null
    {
        return $this->property($name)?->type();
    }

    public function resolver(): ResolverContract
    {
        return $this->resolver;
    }
}
