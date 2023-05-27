<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use ResourceParserGenerator\Contracts\AttributeContract;
use ResourceParserGenerator\Contracts\ClassMethodScopeContract;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
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
        private readonly DeclaredTypeConverterContract $declaredTypeParser,
        private readonly DocBlockParserContract $docBlockParser,
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

    public function attribute(string $className): AttributeContract|null
    {
        foreach ($this->node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $resolvedAttribute = $this->resolver->resolveClass($attr->name->toString());
                if ($resolvedAttribute === $className) {
                    return ClassMethodAttribute::create($attr, $this->resolver);
                }
            }
        }

        return null;
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

    /**
     * TODO Refactor this and parameters to not need both
     *
     * @return Collection<string, TypeContract>
     */
    public function promotedParameters(): Collection
    {
        $promoted = collect();

        foreach ($this->node->params as $param) {
            if (!($param instanceof Param) || !($param->var instanceof Variable) || !is_string($param->var->name)) {
                continue;
            }

            $isPublic = ($param->flags & Class_::MODIFIER_PUBLIC) !== 0
                || ($param->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
            if ($isPublic) {
                $promoted->put($param->var->name, $this->declaredTypeParser->convert($param->type, $this->resolver));
            }
        }

        return $promoted;
    }

    public function returnType(): TypeContract
    {
        return $this->docBlock()?->return()
            ?? ($this->returnType ??= $this->declaredTypeParser->convert($this->node->returnType, $this->resolver));
    }

    public function isPrivate(): bool
    {
        return $this->node->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->node->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->node->isPublic();
    }

    /**
     * @return Collection<string, TypeContract>
     */
    private function buildParameters(): Collection
    {
        $parameters = collect();

        foreach ($this->node->params as $param) {
            $name = $param->var;
            if ($name instanceof Variable) {
                $name = $name->name;
                if (!($name instanceof Expr)) {
                    $parameters->put($name, $this->declaredTypeParser->convert($param->type, $this->resolver));
                } else {
                    throw new RuntimeException('Unexpected expression in variable name');
                }
            }
        }

        return $parameters;
    }
}
