<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Builders\Constraints;

class CompoundConstraint implements ConstraintContract
{
    /**
     * @var ConstraintContract[]
     */
    private readonly array $constraints;

    public function __construct(
        ConstraintContract ...$constraints
    ) {
        $this->constraints = $constraints;
    }

    public function constraint(): string
    {
        $constraints = collect($this->constraints)
            ->map(fn(ConstraintContract $constraint) => $constraint->constraint())
            ->unique()
            ->sort()
            ->join(', ');

        return 'union(' . $constraints . ')';
    }

    public function imports(): array
    {
        return collect($this->constraints)
            ->map(fn(ConstraintContract $constraint) => $constraint->imports())
            ->flatten()
            ->add('union')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }
}
