<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Resolvers\Contracts\VariableResolverContract;
use ResourceParserGenerator\Types\Contracts\TypeContract;

class VariableResolver implements VariableResolverContract
{
    /**
     * @param Collection<string, TypeContract> $variables
     */
    public function __construct(
        private readonly Collection $variables
    ) {
        //
    }

    /**
     * @param Collection<string, TypeContract> $variables
     * @return VariableResolver
     */
    public static function create(Collection $variables): self
    {
        return resolve(self::class, [
            'variables' => $variables,
        ]);
    }

    public function resolve(string $name): TypeContract|null
    {
        return $this->variables->get($name);
    }
}
