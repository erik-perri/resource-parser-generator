<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Scopes;

use RuntimeException;

class FileScope
{
    /**
     * @var array<ClassScope>
     */
    private array $classes = [];

    /**
     * @var array<string, class-string>
     */
    private array $imports = [];

    private string|null $namespace = null;

    public function __construct()
    {
        //
    }

    public static function create(): self
    {
        return resolve(self::class);
    }

    public function addClass(ClassScope $classScope): self
    {
        $this->classes[] = $classScope;

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

        $this->imports[$alias] = $class;

        return $this;
    }

    /**
     * @return ClassScope[]
     */
    public function classes(): array
    {
        return $this->classes;
    }

    public function class(string $name): ClassScope
    {
        foreach ($this->classes as $class) {
            if ($class->name === $name) {
                return $class;
            }
        }

        throw new RuntimeException(sprintf('Class "%s" not found', $name));
    }

    /**
     * @return array<string, class-string>
     */
    public function imports(): array
    {
        return $this->imports;
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
