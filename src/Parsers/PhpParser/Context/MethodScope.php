<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionException;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnFinder;
use ResourceParserGenerator\Parsers\PhpParser\SimpleTypeConverter;

class MethodScope implements ResolverContract
{
    public function __construct(
        public readonly ClassScope $scope,
        private readonly ClassMethod $classMethod,
        private readonly ClassMethodReturnFinder $returnFinder,
        private readonly SimpleTypeConverter $typeConverter,
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

    public function ast(): ClassMethod
    {
        return $this->classMethod;
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
                $types = $this->typeConverter->convert($param->type);

                return [$name => $types];
            })
            ->all();
    }

    /**
     * @return string[]
     * @throws ParseResultException|ReflectionException
     */
    public function returnTypes(): array
    {
        return $this->returnFinder->find($this);
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

    /**
     * @throws ParseResultException
     */
    public function resolveClass(Name $name): string
    {
        return $this->scope->resolveClass($name);
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
