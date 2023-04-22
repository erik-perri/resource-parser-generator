<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use ResourceParserGenerator\Exceptions\ParseResultException;

class MethodScope implements ResolverContract
{
    /**
     * @param ClassScope $scope
     * @param ClassMethod $classMethod
     */
    public function __construct(
        private readonly ClassScope $scope,
        private readonly ClassMethod $classMethod,
    ) {
        //
    }

    public static function create(ClassScope $scope, ClassMethod $classMethod): self
    {
        return resolve(self::class, [
            'scope' => $scope,
            'classMethod' => $classMethod,
        ]);
    }

    public function name(): string
    {
        return $this->classMethod->name->toString();
    }

    /**
     * @return array<string, string[]>
     * @throws ParseResultException
     */
    public function parameters(): array
    {
        return collect($this->classMethod->params)
            ->mapWithKeys(function (Param $param) {
                $name = $this->parseParameterName($param->var);
                $types = $this->parseParameterType($param->type);

                return [$name => $types];
            })
            ->all();
    }

    /**
     * @return string[]
     * @throws ParseResultException
     */
    public function returnTypes(): array
    {
        return $this->parseParameterType($this->classMethod->returnType);
    }

    /**
     * @return string[]
     * @throws ParseResultException
     */
    private function parseParameterType(null|Identifier|Name|ComplexType $type): array
    {
        if ($type === null) {
            return ['mixed'];
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return [$this->parseTypeName($type)];
        }

        if ($type instanceof NullableType) {
            return array_unique(['null', $this->parseTypeName($type->type)]);
        }

        if ($type instanceof UnionType) {
            return array_unique(array_merge(...array_map(
                fn(null|Identifier|Name|ComplexType $type) => $this->parseParameterType($type),
                $type->types,
            )));
        }

        throw new ParseResultException('Unhandled parameter type' . $type->getType(), $type);
    }

    /**
     * @throws ParseResultException
     */
    private function parseParameterName(Error|Variable $var): string
    {
        if ($var instanceof Error) {
            throw new ParseResultException('Unexpected error in parameter name', $var);
        }

        $name = $var->name;
        if ($name instanceof Expr) {
            throw new ParseResultException('Unexpected expression in parameter name', $var);
        }

        return $name;
    }

    private function parseTypeName(Identifier|Name $type): string
    {
        if ($type instanceof Identifier) {
            return $type->name;
        }

        return $type->toString();
    }

    public function resolveClass(string $class): string
    {
        return $this->scope->resolveClass($class);
    }

    /**
     * @return string[]
     * @throws ParseResultException
     */
    public function resolveVariable(string $variable): array
    {
        if ($variable === 'this') {
            return [$this->scope->fullyQualifiedClassName()];
        }

        $parameters = $this->parameters();

        if (array_key_exists($variable, $parameters)) {
            return $parameters[$variable];
        }

        throw new ParseResultException('Variable "' . $variable . '" not found', $this->classMethod);
    }

    /**
     * @return Stmt[]
     * @throws ParseResultException
     */
    public function statements(): array
    {
        if (!$this->classMethod->stmts) {
            throw new ParseResultException('Method "' . $this->name() . '" has no statements', $this->classMethod);
        }
        return $this->classMethod->stmts;
    }
}
