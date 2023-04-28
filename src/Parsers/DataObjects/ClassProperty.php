<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\DataObjects;

use PhpParser\Node\Stmt\Class_;
use ResourceParserGenerator\Contracts\TypeContract;

class ClassProperty
{
    public function __construct(
        public readonly string $name,
        public readonly TypeContract|null $type,
        public readonly int $flags,
    ) {
        //
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

    public function isReadonly(): bool
    {
        return (bool)($this->flags & Class_::MODIFIER_READONLY);
    }

    public function isStatic(): bool
    {
        return (bool)($this->flags & Class_::MODIFIER_STATIC);
    }
}
