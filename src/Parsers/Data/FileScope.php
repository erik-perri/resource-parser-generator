<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use RuntimeException;

class FileScope
{
    /**
     * @var Collection<int, ClassScopeContract>
     */
    private readonly Collection $classes;

    /**
     * @var Collection<int, ClassScopeContract>
     */
    private readonly Collection $enums;

    /**
     * @var Collection<int, ClassScopeContract>
     */
    private readonly Collection $traits;

    /**
     * @var Collection<string, class-string>
     */
    private readonly Collection $imports;

    private string|null $namespace = null;

    public function __construct()
    {
        // TODO Combine classes, enums, and traits into classLikes?
        $this->classes = collect();
        $this->enums = collect();
        $this->traits = collect();
        $this->imports = collect();
    }

    public static function create(): self
    {
        return resolve(self::class);
    }

    public function addClass(ClassScopeContract $classScope): self
    {
        $this->classes->push($classScope);

        return $this;
    }

    public function addEnum(ClassScopeContract $enumScope): self
    {
        $this->enums->push($enumScope);

        return $this;
    }

    /**
     * @param string $alias
     * @param class-string $class
     * @return FileScope
     */
    public function addImport(string $alias, string $class): self
    {
        if (isset($this->imports[$alias])) {
            throw new RuntimeException(sprintf('Alias "%s" already exists', $alias));
        }

        $this->imports->put($alias, $class);

        return $this;
    }

    public function addTrait(ClassScopeContract $traitScope): self
    {
        $this->traits->push($traitScope);

        return $this;
    }

    public function hasClass(string $name): bool
    {
        return $this->classes->contains(fn(ClassScopeContract $class) => $class->name() === $name);
    }

    /**
     * @return Collection<int, ClassScopeContract>
     */
    public function classes(): Collection
    {
        return $this->classes->collect();
    }

    public function class(string $name): ClassScopeContract
    {
        $class = $this->classes->first(
            fn(ClassScopeContract $class) => $class->name() === $name,
        );
        if ($class === null) {
            throw new RuntimeException(sprintf('Class "%s" not found', $name));
        }

        return $class;
    }

    public function enum(string $string): ClassScopeContract
    {
        $enum = $this->enums->first(
            fn(ClassScopeContract $enum) => $enum->name() === $string,
        );
        if ($enum === null) {
            throw new RuntimeException(sprintf('Enum "%s" not found', $string));
        }

        return $enum;
    }

    /**
     * @return Collection<int, ClassScopeContract>
     */
    public function enums(): Collection
    {
        return $this->enums->collect();
    }

    /**
     * @return Collection<string, class-string>
     */
    public function imports(): Collection
    {
        return $this->imports->collect();
    }

    public function namespace(): string|null
    {
        return $this->namespace;
    }

    public function setNamespace(string|null $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function trait(string $name): ClassScopeContract
    {
        $trait = $this->traits->first(
            fn(ClassScopeContract $trait) => $trait->name() === $name,
        );
        if ($trait === null) {
            throw new RuntimeException(sprintf('Trait "%s" not found', $name));
        }

        return $trait;
    }

    /**
     * @return Collection<int, ClassScopeContract>
     */
    public function traits(): Collection
    {
        return $this->traits->collect();
    }
}
