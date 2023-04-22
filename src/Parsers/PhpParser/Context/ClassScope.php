<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser\Context;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Void_;
use ReflectionClass;
use ReflectionException;
use ReflectionUnionType;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use RuntimeException;

class ClassScope implements ResolverContract
{
    private string $className;

    /**
     * @var array<MethodScope|VirtualFunctionScope>
     */
    private array $methods = [];

    /**
     * @var array<string, string[]>
     */
    private array $properties = [];

    public function __construct(
        public readonly FileScope $scope,
        private readonly DocBlockFactory $docBlockFactory,
        private readonly DocBlockTagTypeConverter $convertDocblockTagTypes,
    ) {
        //
    }

    public static function create(FileScope $scope): self
    {
        return resolve(self::class, ['scope' => $scope]);
    }

    /**
     * @param string $name
     * @param string[] $types
     * @return $this
     */
    public function addProperty(string $name, array $types): self
    {
        $this->properties[$name] = $types;

        return $this;
    }

    public function className(): string
    {
        return $this->className;
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedClassName(): string
    {
        /**
         * @var class-string $classString
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $classString = $this->scope->namespace() . '\\' . $this->className;

        return $classString;
    }

    /**
     * @throws ReflectionException
     */
    public function method(string $methodName): MethodScope|VirtualFunctionScope|null
    {
        $methodScope = collect($this->methods)
            ->first(fn(MethodScope|VirtualFunctionScope $method) => $method->name() === $methodName);

        if ($methodScope) {
            return $methodScope;
        }

        return VirtualFunctionScope::create(
            $this->scope,
            $methodName,
            $this->findMethodReturns($methodName),
        );
    }

    public function resolveClass(string $class): string
    {
        return $this->scope->resolveClass($class);
    }

    /**
     * @return string[]
     */
    public function resolveVariable(string $variable): array
    {
        throw new RuntimeException('Cannot resolve variable in class scope');
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function setMethod(MethodScope|VirtualFunctionScope $methodScope): self
    {
        $this->methods[$methodScope->name()] = $methodScope;

        return $this;
    }

    /**
     * @param string $name
     * @return string[]|null
     */
    public function propertyTypes(string $name): array|null
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * @return string[]
     * @throws ReflectionException
     */
    private function findMethodReturns(string $methodName): array
    {
        $reflectionClass = new ReflectionClass($this->fullyQualifiedClassName());
        $reflectedMethod = $reflectionClass->getMethod($methodName);

        // Next check the docblock on the actual method
        if ($reflectedMethod->getDocComment()) {
            $docBlock = $this->docBlockFactory->create($reflectedMethod->getDocComment());

            $returns = $docBlock->getTagsByName('return');
            $return = count($returns) ? $returns[0] : null;

            if ($return) {
                $type = $return instanceof TagWithType
                    ? $return->getType()
                    : new Compound([new Mixed_(), new Void_()]);

                return $this->convertDocblockTagTypes->convert($type, $this);
            }
        }

        // Finally fall back to the actual typed return
        $returnType = $reflectedMethod->getReturnType();
        if ($returnType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($returnType->getTypes() as $type) {
                $types[] = $type->getName();
            }
        } else {
            if ($returnType) {
                // @phpstan-ignore-next-line -- ReflectionType::getName() is not missing
                $types = [$returnType->getName()];
            } else {
                $types = ['mixed', 'void'];
            }
        }

        return $types;
    }
}
