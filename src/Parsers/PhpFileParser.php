<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use ReflectionClass;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\Parsers\PhpFileParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Parsers\Data\ClassScope;
use ResourceParserGenerator\Parsers\Data\EnumScope;
use ResourceParserGenerator\Parsers\Data\FileScope;
use ResourceParserGenerator\Parsers\Data\ReflectedClassScope;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use ResourceParserGenerator\Resolvers\Resolver;
use ResourceParserGenerator\Types\UntypedType;
use RuntimeException;

class PhpFileParser implements PhpFileParserContract
{
    public function __construct(
        private readonly Parser $parser,
        private readonly NodeFinder $nodeFinder,
        private readonly ClassFileLocatorContract $classFileLocator,
        private readonly DeclaredTypeConverterContract $declaredTypeConverter,
    ) {
        //
    }

    public function parse(string $contents, string $staticContext = null): FileScope
    {
        $scope = FileScope::create();

        $ast = $this->parser->parse($contents);
        if (!$ast) {
            throw new RuntimeException('Could not parse file');
        }

        $this->parseNamespaceStatement($ast, $scope);

        $this->parseUseStatements($ast, $scope);
        $this->parseGroupUseStatements($ast, $scope);
        $this->parseClassStatements($ast, $scope, $staticContext);
        $this->parseTraitStatements($ast, $scope);
        $this->parseEnumStatements($ast, $scope);

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
     * @param class-string|null $staticContext
     * @return void
     */
    private function parseClassStatements(array $ast, FileScope $scope, string|null $staticContext): void
    {
        /**
         * @var Class_[] $classes
         */
        $classes = $this->nodeFinder->findInstanceOf($ast, Class_::class);

        if (!count($classes)) {
            return;
        }

        $classResolver = ClassNameResolver::create($scope);

        foreach ($classes as $class) {
            $className = $class->name
                ? $class->name->toString()
                : sprintf('AnonymousClass%d', $class->getLine());

            /**
             * @var class-string $fullyQualifiedClassName
             */
            $fullyQualifiedClassName = $scope->namespace()
                ? sprintf('%s\\%s', $scope->namespace(), $className)
                : $className;

            $resolver = Resolver::create($classResolver, null, $staticContext ?? $fullyQualifiedClassName);

            $classScope = ClassScope::create(
                $fullyQualifiedClassName,
                $class,
                $resolver,
                $this->parseClassExtends($class, $resolver),
                $this->parseClassTraits($class, $resolver),
                null,
            );

            $scope->addClass($classScope);
        }
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseTraitStatements(array $ast, FileScope $scope): void
    {
        /**
         * @var Trait_[] $traits
         */
        $traits = $this->nodeFinder->findInstanceOf($ast, Trait_::class);

        if (!count($traits)) {
            return;
        }

        $classResolver = ClassNameResolver::create($scope);

        foreach ($traits as $trait) {
            $traitName = $trait->name
                ? $trait->name->toString()
                : sprintf('AnonymousTrait%d', $trait->getLine());

            /**
             * @var class-string $fullyQualifiedTraitName
             */
            $fullyQualifiedTraitName = $scope->namespace()
                ? sprintf('%s\\%s', $scope->namespace(), $traitName)
                : $traitName;

            $resolver = Resolver::create($classResolver, null, $fullyQualifiedTraitName);

            $traitScope = ClassScope::create(
                $fullyQualifiedTraitName,
                $trait,
                $resolver,
                null,
                $this->parseClassTraits($trait, $resolver),
                null,
            );

            $scope->addTrait($traitScope);
        }
    }

    private function parseClassExtends(Class_ $class, ResolverContract $resolver): ClassScopeContract|null
    {
        $parent = $class->extends?->toString();
        if (!$parent) {
            return null;
        }

        $parentClassName = $resolver->resolveClass($parent);
        if (!$parentClassName) {
            throw new RuntimeException(sprintf('Could not resolve class "%s"', $parent));
        }

        if (class_exists($parentClassName) && !$this->classFileLocator->exists($parentClassName)) {
            return ReflectedClassScope::create(new ReflectionClass($parentClassName));
        }

        if (!$this->classFileLocator->exists($parentClassName)) {
            throw new RuntimeException(sprintf('Could not find class file for "%s"', $parentClassName));
        }

        $parentClassFile = $this->classFileLocator->get($parentClassName);
        $parentFileScope = $this->parse(File::get($parentClassFile), $resolver->resolveThis());
        $parentClassScope = $parentFileScope->classes()->first();

        if (!$parentClassScope) {
            throw new RuntimeException(sprintf('Could not find parent class "%s"', $parentClassName));
        }

        return $parentClassScope;
    }

    /**
     * @param ClassLike $class
     * @param Resolver $resolver
     * @return Collection<int, ClassScopeContract>
     */
    private function parseClassTraits(ClassLike $class, Resolver $resolver): Collection
    {
        $traits = collect();

        foreach ($class->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $traitName = $trait->toString();
                $traitClassName = $resolver->resolveClass($traitName);
                if (!$traitClassName) {
                    throw new RuntimeException(sprintf('Could not resolve trait "%s"', $traitName));
                }

                if (!$this->classFileLocator->exists($traitClassName)) {
                    throw new RuntimeException(sprintf('Could not find class file for "%s"', $traitClassName));
                }

                $traitClassFile = $this->classFileLocator->get($traitClassName);
                $traitFileScope = $this->parse(File::get($traitClassFile));
                $traitClassScope = $traitFileScope->traits()->first();

                if (!$traitClassScope) {
                    throw new RuntimeException(sprintf('Could not find trait class "%s"', $traitClassName));
                }

                $traits->add($traitClassScope);
            }
        }

        return $traits;
    }

    /**
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseEnumStatements(array $ast, FileScope $scope): void
    {
        /**
         * @var Enum_[] $enums
         */
        $enums = $this->nodeFinder->findInstanceOf($ast, Enum_::class);

        if (!count($enums)) {
            return;
        }

        $classResolver = ClassNameResolver::create($scope);

        foreach ($enums as $enum) {
            $enumName = $enum->name
                ? $enum->name->toString()
                : sprintf('AnonymousEnum%d', $enum->getLine());

            /**
             * @var class-string $fullyQualifiedEnumName
             */
            $fullyQualifiedEnumName = $scope->namespace()
                ? sprintf('%s\\%s', $scope->namespace(), $enumName)
                : $enumName;

            $resolver = Resolver::create($classResolver, null, $fullyQualifiedEnumName);

            $type = $enum->scalarType
                ? $this->declaredTypeConverter->convert($enum->scalarType, $resolver)
                : new UntypedType();

            $enumScope = EnumScope::create(
                $fullyQualifiedEnumName,
                $type,
            );

            $scope->addEnum($enumScope);
        }
    }
}
