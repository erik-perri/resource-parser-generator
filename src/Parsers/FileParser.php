<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use ResourceParserGenerator\Parsers\PhpParser\Context\FileScope;
use ResourceParserGenerator\Visitors\ClassScopeVisitor;
use ResourceParserGenerator\Visitors\FileScopeVisitor;
use RuntimeException;

class FileParser
{
    public function __construct(private readonly Parser $parser)
    {
        //
    }

    public function parse(string $file): FileScope
    {
        $ast = $this->parser->parse(File::get($file));
        if (!$ast) {
            throw new RuntimeException('Failed to parse file "' . $file . '"');
        }

        $fileScope = FileScope::create($file);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(FileScopeVisitor::create($fileScope));
        $traverser->addVisitor(ClassScopeVisitor::create($fileScope));
        $traverser->traverse($ast);

        return $fileScope;
    }
}
