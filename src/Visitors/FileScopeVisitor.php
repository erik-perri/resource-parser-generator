<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\Context\FileScope;

class FileScopeVisitor extends NodeVisitorAbstract
{
    public function __construct(private readonly FileScope $scope)
    {
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
        if ($node instanceof Namespace_) {
            if ($node->name === null) {
                throw new ParseResultException('Unexpected null namespace name', $node);
            }
            $this->scope->setNamespace($node->name->toString());
        }

        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                if (!($use instanceof Node\Stmt\UseUse)) {
                    throw new ParseResultException('Unhandled "use" statement type', $use);
                }

                $this->scope->addImport($use->getAlias()->name, $use->name->toString());
            }
        }
    }
}
