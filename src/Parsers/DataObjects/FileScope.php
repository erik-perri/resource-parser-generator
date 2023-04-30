<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use RuntimeException;

class FileScope
{
    /**
     * @var Collection<int, ClassScope>
     */
    private readonly Collection $classes;

    /**
     * @var Collection<string, class-string>
     */
    private readonly Collection $imports;

    private string|null $namespace = null;

    public function __construct()
    {
        $this->classes = collect();
        $this->imports = collect();
    }

    public static function create(): self
    {
        return resolve(self::class);
    }

    public function addClass(ClassScope $classScope): self
    {
        $this->classes->push($classScope);

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

    public function hasClass(string $name): bool
    {
        return $this->classes->contains(fn(ClassScope $class) => $class->name() === $name);
    }

    /**
     * @return Collection<int, ClassScope>
     */
    public function classes(): Collection
    {
        return $this->classes->collect();
    }

    public function class(string $name): ClassScope
    {
        $class = $this->classes->first(
            fn(ClassScope $class) => $class->name() === $name,
        );
        if ($class === null) {
            throw new RuntimeException(sprintf('Class "%s" not found', $name));
        }

        return $class;
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
}
