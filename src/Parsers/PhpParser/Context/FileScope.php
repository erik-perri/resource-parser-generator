<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor\NameResolver;
use ResourceParserGenerator\Exceptions\ParseResultException;
use RuntimeException;

class FileScope implements ResolverContract
{
    /**
     * @var ClassScope[]
     */
    private array $classes = [];

    public function __construct(
        private readonly string $fileName,
        private readonly NameResolver $nameResolver,
    ) {
        //
    }

    public static function create(string $fileName, NameResolver $nameResolver): self
    {
        return resolve(self::class, [
            'fileName' => $fileName,
            'nameResolver' => $nameResolver,
        ]);
    }

    public function addClass(ClassScope $classScope): self
    {
        $this->classes[$classScope->fullyQualifiedClassName()] = $classScope;

        return $this;
    }

    public function namespace(): string
    {
        $namespace = $this->nameResolver->getNameContext()->getNamespace();
        if (!$namespace) {
            throw new RuntimeException('Cannot resolve namespace in file scope of "' . $this->fileName . '"');
        }

        return $namespace->toString();
    }

    /**
     * @throws ParseResultException
     */
    public function resolveClass(Name $name): string
    {
        $resolved = $this->nameResolver->getNameContext()->getResolvedName($name, Use_::TYPE_NORMAL);
        if (!$resolved) {
            throw new ParseResultException('Cannot resolve class name "' . $name->toString() . '"', $name);
        }

        return $resolved->toString();
    }

    /**
     * @return string[]
     */
    public function resolveVariable(string $variable): array
    {
        throw new RuntimeException('Cannot resolve variable in file scope');
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
