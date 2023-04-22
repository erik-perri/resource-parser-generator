<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use RuntimeException;

class FileScope implements ResolverContract
{
    private string $namespace;

    /**
     * @param ClassScope[] $classes
     * @param array<string, string> $imports
     */
    public function __construct(
        public readonly string $fileName,
        private array $classes = [],
        private array $imports = [],
    ) {
        //
    }

    public static function create(string $fileName): self
    {
        return resolve(self::class, ['fileName' => $fileName]);
    }

    public function addClass(ClassScope $classScope): self
    {
        $this->classes[$classScope->fullyQualifiedClassName()] = $classScope;

        return $this;
    }

    public function addImport(string $alias, string $class): self
    {
        $this->imports = array_merge($this->imports, [$alias => $class]);

        return $this;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function resolveClass(string $class): string
    {
        return $this->imports[$class] ?? $class;
    }

    /**
     * @return string[]
     */
    public function resolveVariable(string $variable): array
    {
        throw new RuntimeException('Cannot resolve variable in file scope');
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param class-string $className
     * @return ClassScope|null
     */
    public function class(string $className): ?ClassScope
    {
        return $this->classes[$className] ?? null;
    }
}
