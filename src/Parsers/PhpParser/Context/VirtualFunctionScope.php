<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;
use RuntimeException;

class VirtualFunctionScope implements ResolverContract
{
    /**
     * @param FileScope|ClassScope $scope
     * @param string $name
     * @param string[] $returnTypes
     */
    public function __construct(
        private readonly FileScope|ClassScope $scope,
        private readonly string $name,
        private readonly array $returnTypes,
    ) {
        //
    }

    /**
     * @param FileScope|ClassScope $scope
     * @param string $name
     * @param string[] $returnTypes
     * @return VirtualFunctionScope
     */
    public static function create(
        FileScope|ClassScope $scope,
        string $name,
        array $returnTypes
    ): self {
        return resolve(self::class, [
            'scope' => $scope,
            'name' => $name,
            'returnTypes' => $returnTypes,
        ]);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function resolveClass(Name $name): string
    {
        return $this->scope->resolveClass($name);
    }

    /**
     * @return string[]
     */
    public function resolveVariable(string $variable): array
    {
        throw new RuntimeException(
            'Cannot resolve variable "' . $variable . '" in virtual function "' . $this->name() . '"',
        );
    }

    /**
     * @return string[]
     */
    public function returnTypes(): array
    {
        return $this->returnTypes;
    }
}
