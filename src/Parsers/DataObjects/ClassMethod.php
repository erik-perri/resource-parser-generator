<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\TypeContract;

class ClassMethod
{
    /**
     * @param string $name
     * @param TypeContract $returnType
     * @param int $flags
     * @param Collection<string, TypeContract> $parameters
     * @param DocBlock|null $docBlock
     */
    public function __construct(
        public readonly string $name,
        public readonly TypeContract $returnType,
        public readonly int $flags,
        private readonly Collection $parameters,
        public readonly DocBlock|null $docBlock,
    ) {
        //
    }

    /**
     * @param string $name
     * @param TypeContract $returnType
     * @param int $flags
     * @param Collection<string, TypeContract> $parameters
     * @param DocBlock|null $docBlock
     * @return ClassMethod
     */
    public static function create(
        string $name,
        TypeContract $returnType,
        int $flags,
        Collection $parameters,
        DocBlock|null $docBlock,
    ): self {
        return resolve(self::class, [
            'name' => $name,
            'returnType' => $returnType,
            'flags' => $flags,
            'parameters' => $parameters,
            'docBlock' => $docBlock,
        ]);
    }

    /**
     * @return Collection<string, TypeContract>
     */
    public function parameters(): Collection
    {
        return $this->parameters->collect();
    }

    public function isPrivate(): bool
    {
        return (bool)($this->flags & Class_::MODIFIER_PRIVATE);
    }

    public function isProtected(): bool
    {
        return (bool)($this->flags & Class_::MODIFIER_PROTECTED);
    }

    public function isPublic(): bool
    {
        return ($this->flags & Class_::MODIFIER_PUBLIC) !== 0
            || ($this->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
    }
}
