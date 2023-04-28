<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use ResourceParserGenerator\Parsers\Scopes\ClassScope;
use ResourceParserGenerator\Parsers\Scopes\FileScope;
use RuntimeException;

class PhpFileParser
{
    public function __construct(private readonly Parser $parser)
    {
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
     * @param Stmt[] $ast
     * @param FileScope $scope
     * @return void
     */
    private function parseNamespaceStatement(array $ast, FileScope $scope): void
    {
        $nodeFinder = new NodeFinder();

        /**
         * @var Namespace_[] $namespaces
         */
        $namespaces = $nodeFinder->findInstanceOf($ast, Namespace_::class);
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
        $nodeFinder = new NodeFinder();

        /**
         * @var Use_[] $uses
         */
        $uses = $nodeFinder->findInstanceOf($ast, Use_::class);
        if (!count($uses)) {
            return;
        }

        foreach ($uses as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                /**
                 * @var class-string $className
                 */
                $className = $useUse->name->toString();
                $scope->addImport(
                    $useUse->alias?->toString() ?? $useUse->name->getLast(),
                    $className,
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
        $nodeFinder = new NodeFinder();

        /**
         * @var GroupUse[] $uses
         */
        $uses = $nodeFinder->findInstanceOf($ast, GroupUse::class);
        if (!count($uses)) {
            return;
        }

        foreach ($uses as $use) {
            $prefix = $use->prefix->toString();

            foreach ($use->uses as $useUse) {
                /**
                 * @var class-string $className
                 */
                $className = sprintf('%s\\%s', $prefix, $useUse->name->toString());
                $scope->addImport(
                    $useUse->alias?->toString() ?? $useUse->name->getLast(),
                    $className,
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
        $nodeFinder = new NodeFinder();

        /**
         * @var Class_[] $classes
         */
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);

        if (!count($classes)) {
            return;
        }

        foreach ($classes as $class) {
            $className = $class->name
                ? $class->name->toString()
                : sprintf('AnonymousClass%d', $class->getLine());

            $classScope = ClassScope::create($scope, $className);

            $scope->addClass($classScope);
        }
    }
}
