<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\DeclaredTypeParser;
use ResourceParserGenerator\Parsers\DocBlockParser;
use RuntimeException;

class ClassMethodScope implements ClassMethodScopeContract
{
    private DocBlock|null $docBlock = null;

    /**
     * @var Collection<string, TypeContract>
     */
    private Collection $parameters;
    private TypeContract $returnType;

    public function __construct(
        private readonly ClassMethod $node,
        private readonly ResolverContract $resolver,
        private readonly DeclaredTypeParser $declaredTypeParser,
        private readonly DocBlockParser $docBlockParser,
    ) {
        //
    }

    public static function create(ClassMethod $node, ResolverContract $resolver): self
    {
        return resolve(self::class, [
            'node' => $node,
            'resolver' => $resolver,
        ]);
    }

    public function docBlock(): DocBlock|null
    {
        if ($this->docBlock === null && $this->node->getDocComment() !== null) {
            $this->docBlock = $this->docBlockParser->parse(
                $this->node->getDocComment()->getText(),
                $this->resolver,
            );
        }

        return $this->docBlock;
    }

    public function name(): string
    {
        return $this->node->name->toString();
    }

    public function node(): ClassMethod
    {
        return $this->node;
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function parameters(): Collection
    {
        return $this->parameters ??= $this->buildParameters();
    }

    public function returnType(): TypeContract
    {
        return $this->docBlock()?->return()
            ?? ($this->returnType ??= $this->declaredTypeParser->parse($this->node->returnType, $this->resolver));
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function buildParameters(): Collection
    {
        $parameters = collect();

        foreach ($this->node->params as $param) {
            $name = $param->var;
            if ($name instanceof Variable) {
                $name = $name->name;
                if (!($name instanceof Expr)) {
                    $parameters->put($name, $this->declaredTypeParser->parse($param->type, $this->resolver));
                } else {
                    throw new RuntimeException('Unexpected expression in variable name');
                }
            }
        }

        return $parameters;
    }

    public function isPrivate(): bool
    {
        return (bool)($this->node->flags & Class_::MODIFIER_PRIVATE);
    }

    public function isProtected(): bool
    {
        return (bool)($this->node->flags & Class_::MODIFIER_PROTECTED);
    }

    public function isPublic(): bool
    {
        return ($this->node->flags & Class_::MODIFIER_PUBLIC) !== 0
            || ($this->node->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
    }
}
