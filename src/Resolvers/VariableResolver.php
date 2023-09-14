<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Resolvers\VariableResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class VariableResolver implements VariableResolverContract
{
    /**
     * @param Collection<string, TypeContract> $variables
     */
    public function __construct(
        private readonly Collection $variables,
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

    /**
     * @param Collection<string, TypeContract> $variables
     * @return self
     */
    public function extend(Collection $variables): self
    {
        return self::create($this->variables->merge($variables));
    }

    public function resolve(string $name): TypeContract|null
    {
        return $this->variables->get($name);
    }
}
