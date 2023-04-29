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
     */
    public function __construct(
        public readonly string $name,
        public readonly TypeContract $returnType,
        public readonly int $flags,
        private readonly Collection $parameters,
    ) {
        //
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
