<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\ClassLike;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class EnumScope extends ClassScope
{
    /**
     * @param class-string $fullyQualifiedName
     * @param ClassLike $node
     * @param ResolverContract $resolver
     * @param ClassScopeContract|null $extends
     * @param Collection<int, ClassScopeContract> $traits
     * @param DocBlockParserContract $docBlockParser
     * @param TypeContract $backingType
     */
    public function __construct(
        string $fullyQualifiedName,
        ClassLike $node,
        ResolverContract $resolver,
        ClassScopeContract|null $extends,
        Collection $traits,
        DocBlockParserContract $docBlockParser,
        private readonly TypeContract $backingType,
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
