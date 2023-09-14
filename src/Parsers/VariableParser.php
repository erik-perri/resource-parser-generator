<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Types;

class VariableParser
{
    public function __construct(
        private readonly DocBlockParser $docBlockParser,
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly NodeFinder $nodeFinder,
    ) {
        //
    }

    /**
     * @param Stmt $stmt
     * @param ResolverContract $resolver
     * @return Collection<string, TypeContract>
     */
    public function parse(Stmt $stmt, ResolverContract $resolver): Collection
    {
        /**
         * @var Collection<string, TypeContract> $variables
         */
        $variables = collect();

        /**
         * @var Assign[] $assignNodes
         */
        $assignNodes = $this->nodeFinder->findInstanceOf($stmt, Assign::class);

        foreach ($assignNodes as $assignNode) {
            if (!($assignNode->var instanceof Variable)) {
                continue;
            }
            if ($assignNode->var->name instanceof Expr) {
                continue;
            }

            $type = $this->expressionTypeConverter->convert(
                $assignNode->expr,
                ConverterContext::create($resolver),
            );

            if ($assignNode->getDocComment()) {
                $docBlock = $this->docBlockParser->parse($assignNode->getDocComment()->getText(), $resolver);

                $overriddenType = $docBlock->vars->get($assignNode->var->name);
                if ($overriddenType) {
                    $type = $overriddenType;
                }
            }

            $existing = $variables->get($assignNode->var->name);
            if ($existing) {
                if ($existing instanceof Types\UnionType) {
                    $type = $existing->addToUnion($type);
                } else {
                    $type = new Types\UnionType($existing, $type);
                }
            }

            $variables->put($assignNode->var->name, $type);
        }

        return $variables;
    }
}
