<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\EnumCase;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\EnumScopeContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\EnumCaseData;
use RuntimeException;

class EnumScope extends ClassScope implements EnumScopeContract
{
    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param Collection<int, ClassScopeContract> $traits
     * @param DocBlockParserContract $docBlockParser
     * @param TypeContract $backingType
     * @param ExpressionValueParserContract $expressionValueParser
     */
    public function __construct(
        string $fullyQualifiedName,
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        Collection $traits,
        private readonly DocBlockParserContract $docBlockParser,
        private readonly TypeContract $backingType,
        private readonly ExpressionValueParserContract $expressionValueParser,
    ) {
        parent::__construct(
            $fullyQualifiedName,
            $node,
            $resolver,
            $extends,
            $traits,
            collect(),
            $docBlockParser,
        );
    }

    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param Collection<int, ClassScopeContract> $traits
     * @param TypeContract $backingType
     * @return self
     */
    public static function createEnum(
        string $fullyQualifiedName,
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        Collection $traits,
        TypeContract $backingType,
    ): self {
        return resolve(self::class, [
            'fullyQualifiedName' => $fullyQualifiedName,
            'node' => $node,
            'resolver' => $resolver,
            'extends' => $extends,
            'traits' => $traits,
            'backingType' => $backingType,
        ]);
    }

    /**
     * @return Collection<int, EnumCaseData>
     */
    public function cases(): Collection
    {
        return collect($this->node()->stmts)
            ->filter(fn(Stmt $stmt) => $stmt instanceof EnumCase)
            ->map(function (EnumCase $case) {
                if (!$case->expr) {
                    throw new RuntimeException('Unhandled null enum case.');
                }
                return new EnumCaseData(
                    $case->name->name,
                    $this->expressionValueParser->parse($case->expr, $this->resolver()),
                    $case->getDocComment()
                        ? $this->docBlockParser->parse($case->getDocComment()->getText(), $this->resolver())->comment
                        : null,
                );
            });
    }

    /**
     * @return TypeContract
     */
    public function backingType(): TypeContract
    {
        return $this->backingType;
    }

    public function property(string $name): ClassPropertyContract|null
    {
        if ($name === 'value') {
            return VirtualClassProperty::create($this->backingType());
        }

        return parent::property($name);
    }
}
