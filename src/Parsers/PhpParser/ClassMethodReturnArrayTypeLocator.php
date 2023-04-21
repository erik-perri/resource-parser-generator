<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use Closure;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use ResourceParserGenerator\Visitors\FindArrayReturnVisitor;
use ResourceParserGenerator\Visitors\FindClassMethodWithNameVisitor;

class ClassMethodReturnArrayTypeLocator
{
    public function __construct(private readonly Parser $parser)
    {
        //
    }

    /**
     * @param string $classFile
     * @param string $methodName
     * @param Closure(Array_): void $handler
     */
    public function locate(string $classFile, string $methodName, Closure $handler): void
    {
        $ast = $this->parser->parse(File::get($classFile));

        $returnTraverser = new NodeTraverser();
        $returnTraverser->addVisitor(new FindClassMethodWithNameVisitor(
            $methodName,
            function (ClassMethod $method) use ($handler) {
                $arrayTraverser = new NodeTraverser();
                $arrayTraverser->addVisitor(
                    new FindArrayReturnVisitor(function (Array_ $array) use ($handler) {
                        call_user_func($handler, $array);
                    }),
                );
                $arrayTraverser->traverse($method->stmts);
            },
        ));
        $returnTraverser->traverse($ast);
    }
}
