<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use ResourceParserGenerator\Contracts\ClassPropertyContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Parsers\DocBlockParser;

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
        return $this->property->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->property->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->property->isPublic();
    }

    public function isReadonly(): bool
    {
        return $this->property->isReadonly();
    }

    public function isStatic(): bool
    {
        return $this->property->isStatic();
    }
}
