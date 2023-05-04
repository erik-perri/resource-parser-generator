<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Parsers\DocBlockParser;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;

class ClassProperty implements ClassPropertyContract
{
    private DocBlock|null $docBlock = null;
    private TypeContract $type;

    public function __construct(
        private readonly Property $property,
        private readonly PropertyProperty $propertyProperty,
        private readonly ResolverContract $resolver,
        private readonly DeclaredTypeConverter $declaredTypeParser,
        private readonly DocBlockParser $docBlockParser,
    ) {
        //
    }

    public static function create(
        Property $property,
        PropertyProperty $propertyProperty,
        ResolverContract $resolver
    ): self {
        return resolve(self::class, [
            'property' => $property,
            'propertyProperty' => $propertyProperty,
            'resolver' => $resolver,
        ]);
    }

    public function docBlock(): DocBlock|null
    {
        if ($this->docBlock === null && $this->property->getDocComment() !== null) {
            $this->docBlock = $this->docBlockParser->parse(
                $this->property->getDocComment()->getText(),
                $this->resolver,
            );
        }

        return $this->docBlock;
    }

    public function name(): string
    {
        return $this->propertyProperty->name->toString();
    }

    public function type(): TypeContract
    {
        $docBlock = $this->docBlock();
        if ($docBlock?->hasVar('')) {
            return $docBlock->var('');
        }
        if ($docBlock?->hasVar($this->name())) {
            return $docBlock->var($this->name());
        }
        return $this->type ??= $this->declaredTypeParser->convert($this->property->type, $this->resolver);
    }

    public function isPrivate(): bool
    {
        return (bool)($this->property->flags & Class_::MODIFIER_PRIVATE);
    }

    public function isProtected(): bool
    {
        return (bool)($this->property->flags & Class_::MODIFIER_PROTECTED);
    }

    public function isPublic(): bool
    {
        return ($this->property->flags & Class_::MODIFIER_PUBLIC) !== 0
            || ($this->property->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
    }

    public function isReadonly(): bool
    {
        return (bool)($this->property->flags & Class_::MODIFIER_READONLY);
    }

    public function isStatic(): bool
    {
        return (bool)($this->property->flags & Class_::MODIFIER_STATIC);
    }
}
