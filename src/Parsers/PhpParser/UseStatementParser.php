<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
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

        $importedClasses = collect();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($importedClasses) extends NodeVisitorAbstract {
            public function __construct(private readonly Collection $importedClasses)
            {
                //
            }

            public function enterNode(Node $node): void
            {
                if ($node instanceof Node\Stmt\Use_) {
                    foreach ($node->uses as $use) {
                        if (!($use instanceof Node\Stmt\UseUse)) {
                            throw new RuntimeException('Unexpected "use" statement type.');
                        }

                        $this->importedClasses->put($use->getAlias()->name, $use->name->toString());
                    }
                }
            }
        });

        $traverser->traverse($ast);

        return $importedClasses->toArray();
    }
}
