<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use phpDocumentor\Reflection\DocBlock\Tags\Method;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\FileParser;
use ResourceParserGenerator\Parsers\PhpParser\Context\ClassScope;
use ResourceParserGenerator\Parsers\PhpParser\Context\FileScope;
use ResourceParserGenerator\Parsers\PhpParser\Context\VirtualMethodScope;

class ClassScopeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly FileScope $scope,
        private readonly ClassFileFinder $classFileFinder,
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
        private readonly FileParser $fileParser,
    ) {
        //
    }

    public static function create(FileScope $scope): self
    {
        return resolve(self::class, ['scope' => $scope]);
    }

    /**
     * @throws ParseResultException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->processClass($node);
        }
    }

    /**
     * @throws ParseResultException
     */
    private function processClass(Class_ $node): void
    {
        if ($node->name === null) {
            throw new ParseResultException('Unexpected null class name', $node);
        }

        $extends = $node->extends;
        $classScope = null;
        if ($extends) {
            if (!($extends instanceof Name)) {
                throw new ParseResultException('Unexpected extends type', $node);
            }
            /**
             * @var class-string $extendsClassName
             * @noinspection PhpRedundantVariableDocTypeInspection
             */
            $extendsClassName = $this->scope->resolveClass($extends);
            if ($this->classFileFinder->has($extendsClassName)) {
                $extendsClassFile = $this->classFileFinder->find($extendsClassName);
                $extendsClassScope = $this->fileParser->parse($extendsClassFile);

                $extendsClass = $extendsClassScope->class($extendsClassName);
                if (!$extendsClass) {
                    throw new ParseResultException(
                        'Class "' . $extendsClassName . '" not found in file "' . $extendsClassFile . '"',
                        $node,
                    );
                }

                $classScope = $extendsClass->extend($this->scope);
            }
        }

        $classScope ??= ClassScope::create($this->scope);
        $classScope->setClassName($node->name->toString());

        $this->scope->addClass($classScope);

        $comment = $node->getDocComment();
        if ($comment !== null) {
            $this->processDocComment($classScope, $comment, $node);
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(MethodScopeVisitor::create($classScope));
        $traverser->traverse($node->stmts);
    }

    /**
     * @throws ParseResultException
     */
    private function processDocComment(ClassScope $classScope, Doc $doc, Node $node): void
    {
        $docBlock = $this->docBlockFactory->create($doc->getText());

        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof Property || $tag instanceof PropertyRead) {
                $propertyName = $tag->getVariableName();
                if (!$propertyName) {
                    throw new ParseResultException('Unexpected null property name', $node);
                }

                $classScope->addProperty(
                    $propertyName,
                    $this->convertDocblockTagTypes->convert($tag->getType(), $classScope),
                );
            } elseif ($tag instanceof Method) {
                $method = VirtualMethodScope::create(
                    $classScope,
                    $tag->getMethodName(),
                    $this->convertDocblockTagTypes->convert($tag->getReturnType(), $classScope),
                );

                $classScope->setMethod($method);
            }
        }
    }
}
