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
use RuntimeException;

class ClassScope implements ClassScopeContract
{
    private DocBlock|null $docBlock = null;

    /**
     * @var Collection<string, ClassMethodScopeContract>|null
     */
    private Collection|null $methods = null;

    /**
     * @var Collection<string, ClassPropertyContract>|null
     */
    private Collection|null $properties = null;

    /**
     * @var Collection<string, ClassConstantContract>|null
     */
    private Collection|null $constants = null;

    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param Collection<int, ClassScopeContract> $traits
     * @param Collection<int, TypeContract>|null $knownGenerics
     * @param DocBlockParserContract $docBlockParser
     */
    public function __construct(
        private readonly string $fullyQualifiedName,
        private readonly ClassLike $node,
        private readonly ResolverContract $resolver,
        private readonly ClassScopeContract|null $extends,
        private readonly Collection $traits,
        private readonly Collection|null $knownGenerics,
        private readonly DocBlockParserContract $docBlockParser,
    ) {
        //
    }

    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param Collection<int, ClassScopeContract> $traits
     * @param Collection<int, TypeContract>|null $knownGenerics
     * @return self
     */
    public static function create(
        string $fullyQualifiedName,
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        Collection $traits,
        Collection|null $knownGenerics,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedName' => $fullyQualifiedName,
            'node' => $node,
            'resolver' => $resolver,
            'extends' => $extends,
            'traits' => $traits,
            'knownGenerics' => $knownGenerics,
        ]);
    }

    /**
     * @return Collection<string, ClassConstantContract>
     */
    public function constants(): Collection
    {
        return $this->getConstants()->collect();
    }

    public function constant(string $name): ClassConstantContract|null
    {
        // TODO Docblock?

        $constant = $this->getConstants()->get($name);

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

    public function extends(): ClassScopeContract|null
    {
        return $this->extends;
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

    /**
     * @return Collection<int, TypeContract>|null
     */
    public function knownGenerics(): Collection|null
    {
        return $this->knownGenerics;
    }

    /**
     * @return Collection<string, ClassMethodScopeContract>
     */
    public function methods(): Collection
    {
        return $this->getMethods()->collect();
    }

    public function method(string $name): ClassMethodScopeContract|null
    {
        $methodLocators = [
            fn() => $this->docBlock()?->hasMethod($name)
                ? VirtualClassMethodScope::create($this->docBlock()->method($name))
                : null,
            fn() => $this->getMethods()->get($name),
            fn() => $this->traits->first(fn(ClassScopeContract $trait) => (bool)$trait->method($name))?->method($name),
            fn() => $this->parent()?->method($name),
        ];

        foreach ($methodLocators as $locator) {
            $method = $locator();
            if ($method) {
                return $method;
            }
        }

        return null;
    }

    public function name(): string
    {
        return $this->node->name
            ? $this->node->name->toString()
            : sprintf('AnonymousClass%d', $this->node->getLine());
    }

    public function node(): ClassLike
    {
        return $this->node;
    }

    public function parent(): ClassScopeContract|null
    {
        return $this->extends;
    }

    /**
     * @return Collection<string, ClassPropertyContract>
     */
    public function properties(): Collection
    {
        return $this->getProperties()->collect();
    }

    public function property(string $name): ClassPropertyContract|null
    {
        if ($this->docBlock()?->hasProperty($name)) {
            return VirtualClassProperty::create($this->docBlock()->property($name));
        }

        $property = $this->getProperties()->get($name);

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

    /**
     * @return Collection<int, ClassScopeContract>
     */
    public function traits(): Collection
    {
        return $this->traits;
    }

    /**
     * @return Collection<string, ClassConstantContract>
     */
    private function getConstants(): Collection
    {
        if ($this->constants !== null) {
            return $this->constants;
        }

        $this->constants = collect();

        foreach ($this->node->getConstants() as $constantGroup) {
            foreach ($constantGroup->consts as $constant) {
                $constantScope = ClassConstant::create($constant, $this->resolver);
                $this->constants->put($constantScope->name(), $constantScope);
            }
        }

        return $this->constants->collect();
    }

    /**
     * @return Collection<string, ClassMethodScopeContract>
     */
    private function getMethods(): Collection
    {
        if ($this->methods !== null) {
            return $this->methods;
        }

        $this->methods = collect();

        foreach ($this->node->getMethods() as $method) {
            $methodScope = ClassMethodScope::create($method, $this->resolver);
            $this->methods->put($methodScope->name(), $methodScope);
        }

        return $this->methods;
    }

    /**
     * @return Collection<string, ClassPropertyContract>
     */
    private function getProperties(): Collection
    {
        if ($this->properties !== null) {
            return $this->properties;
        }

        $this->properties = collect();

        $constructor = $this->method('__construct');
        if ($constructor) {
            if (!($constructor instanceof ClassMethodScope)) {
                throw new RuntimeException('Unhandled non-concrete constructor');
            }

            foreach ($constructor->promotedParameters() as $promotedName => $promotedType) {
                $this->properties->put(
                    $promotedName,
                    VirtualClassProperty::create($promotedType),
                );
            }
        }

        foreach ($this->node->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $propertyScope = ClassProperty::create($property, $prop, $this->resolver);
                $this->properties->put($propertyScope->name(), $propertyScope);
            }
        }

        return $this->properties;
    }
}
