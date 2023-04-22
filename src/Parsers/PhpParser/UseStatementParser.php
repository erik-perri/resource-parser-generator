<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ResourceParserGenerator\Exceptions\ParseResultException;
use RuntimeException;

class UseStatementParser
{
    public function __construct(private readonly Parser $parser)
    {
        //
    }

    /**
     * @param string $file
     * @return array<string, string>
     */
    public function parse(string $file): array
    {
        $ast = $this->parser->parse(File::get($file));
        if (!$ast) {
            throw new RuntimeException('Failed to parse file "' . $file . '"');
        }

        /**
         * @var Collection<string, string> $importedClasses
         */
        $importedClasses = collect();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($importedClasses) extends NodeVisitorAbstract {
            /**
             * @param Collection<string, string> $classes
             */
            public function __construct(private readonly Collection $classes)
            {
                //
            }

            /** @noinspection PhpMissingReturnTypeInspection */
            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Use_) {
                    foreach ($node->uses as $use) {
                        if (!($use instanceof Node\Stmt\UseUse)) {
                            throw new ParseResultException('Unhandled "use" statement type', $use);
                        }

                        $this->classes->put($use->getAlias()->name, $use->name->toString());
                    }
                }
            }
        });

        $traverser->traverse($ast);

        return $importedClasses->all();
    }
}
