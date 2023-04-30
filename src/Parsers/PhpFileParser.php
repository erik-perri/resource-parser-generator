<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use ResourceParserGenerator\Resolvers\Resolver;
use RuntimeException;

class PhpFileParser
{
    public function __construct(
        private readonly Parser $parser,
        private readonly NodeFinder $nodeFinder,
        private readonly PhpClassParser $classParser,
        private readonly ClassFileLocatorContract $classFileLocator,
    ) {
        //
    }

    public function parse(string $contents): FileScope
    {
        $scope = FileScope::create();

        $ast = $this->parser->parse($contents);
        if (!$ast) {
            throw new RuntimeException('Could not parse file');
        }

        $this->parseNamespaceStatement($ast, $scope);

        $this->parseUseStatements($ast, $scope);
        $this->parseGroupUseStatements($ast, $scope);
        $this->parseClassStatements($ast, $scope);

        return $scope;
    }

    /**
     * @param string|null $namespace
     * @param string $className
     * @return class-string
     */
    private function buildImportClassName(string|null $namespace, string $className): string
    {
        /**
         * @var class-string $fullName
         */
        $fullName = $namespace
            ? sprintf('%s\\%s', $namespace, $className)
            : $className;

        return $fullName;
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseNamespaceStatement(array $ast, FileScope $scope): void
    {
        /**
         * @var Namespace_[] $namespaces
         */
        $namespaces = $this->nodeFinder->findInstanceOf($ast, Namespace_::class);
        if (!count($namespaces)) {
            return;
        }

        if (count($namespaces) > 1) {
            throw new RuntimeException('Multiple namespaces not supported');
        }

        $namespaceName = $namespaces[0]->name;
        if (!$namespaceName) {
            throw new RuntimeException('Unnamed namespaces not supported');
        }

        $scope->setNamespace($namespaceName->toString());
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseUseStatements(array $ast, FileScope $scope): void
    {
        /**
         * @var Use_[] $uses
         */
        $uses = $this->nodeFinder->findInstanceOf($ast, Use_::class);
        if (!count($uses)) {
            return;
        }

        foreach ($uses as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $scope->addImport(
                    $useUse->alias?->toString() ?? $useUse->name->getLast(),
                    $this->buildImportClassName(null, $useUse->name->toString()),
                );
            }
        }
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseGroupUseStatements(array $ast, FileScope $scope): void
    {
        /**
         * @var GroupUse[] $uses
         */
        $uses = $this->nodeFinder->findInstanceOf($ast, GroupUse::class);
        if (!count($uses)) {
            return;
        }

        foreach ($uses as $use) {
            $prefix = $use->prefix->toString();

            foreach ($use->uses as $useUse) {
                $scope->addImport(
                    $useUse->alias?->toString() ?? $useUse->name->getLast(),
                    $this->buildImportClassName($prefix, $useUse->name->toString()),
                );
            }
        }
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseClassStatements(array $ast, FileScope $scope): void
    {
        /**
         * @var Class_[] $classes
         */
        $classes = $this->nodeFinder->findInstanceOf($ast, Class_::class);

        if (!count($classes)) {
            return;
        }

        foreach ($classes as $class) {
            $className = $class->name
                ? $class->name->toString()
                : sprintf('AnonymousClass%d', $class->getLine());

            $classScope = ClassScope::create($className);

            // Add an unparsed version of the class to the scope so we can make use of the resolver to find
            // the fully qualified class name.
            // TODO Restructure this so we can resolve the fully qualified name without this.
            $scope->addClass($classScope);

            $classResolver = ClassNameResolver::create($scope);
            $fullyQualifiedClassName = $classResolver->resolve($className);
            if (!$fullyQualifiedClassName) {
                throw new RuntimeException(sprintf('Could not resolve class "%s"', $className));
            }

            $resolver = Resolver::create($classResolver, $fullyQualifiedClassName);

            if ($fullyQualifiedClassName !== $className) {
                $classScope->fullyQualifiedName = $fullyQualifiedClassName;
            }
            $classScope->extends = $this->parseClassExtends($class, $resolver);

            $this->classParser->parse($class, $classScope, $resolver);
        }
    }

    private function parseClassExtends(Class_ $class, ResolverContract $resolver): ClassScope|null
    {
        $parent = $class->extends?->toString();
        if (!$parent) {
            return null;
        }

        $parentClassName = $resolver->resolveClass($parent);
        if (!$parentClassName) {
            throw new RuntimeException(sprintf('Could not resolve class "%s"', $parent));
        }

        if (!$this->classFileLocator->exists($parentClassName)) {
            throw new RuntimeException(sprintf('Could not find class file for "%s"', $parentClassName));
        }

        $parentClassFile = $this->classFileLocator->get($parentClassName);
        $parentFileScope = $this->parse(File::get($parentClassFile));
        $parentClassScope = $parentFileScope->classes()->first();

        if (!$parentClassScope) {
            throw new RuntimeException(sprintf('Could not find parent class "%s"', $parentClassName));
        }

        return $parentClassScope;
    }
}
