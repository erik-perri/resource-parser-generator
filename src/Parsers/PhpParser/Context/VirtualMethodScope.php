<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Name;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnTypeFinder;
use RuntimeException;

class VirtualMethodScope implements ResolverContract
{
    /**
     * @param ClassScope $scope
     * @param string $name
     * @param string[]|null $returnTypes
     * @param ClassMethodReturnTypeFinder $returnFinder
     */
    public function __construct(
        public readonly ClassScope $scope,
        private readonly string $name,
        private readonly ?array $returnTypes,
        private readonly ClassMethodReturnTypeFinder $returnFinder,
    ) {
        //
    }

    /**
     * @param ClassScope $scope
     * @param string $name
     * @param string[]|null $returnTypes
     * @return VirtualMethodScope
     */
    public static function create(
        ClassScope $scope,
        string $name,
        ?array $returnTypes,
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

    /**
     * @throws ParseResultException
     */
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
     * @throws ParseResultException|ReflectionException
     */
    public function returnTypes(): array
    {
        return $this->returnTypes ?? $this->returnFinder->find($this);
    }
}
