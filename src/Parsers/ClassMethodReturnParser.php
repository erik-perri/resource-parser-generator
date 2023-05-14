<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use ResourceParserGenerator\Contracts\ClassScopeContract as ClassScopeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Parsers\Data\ClassMethodScope;
use ResourceParserGenerator\Resolvers\VariableResolver;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ClassMethodReturnParser
{
    public function __construct(
        private readonly ExprTypeConverter $expressionTypeConverter,
        private readonly ClassParser $classParser,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return TypeContract
     */
    public function parse(string $className, string $methodName): TypeContract
    {
        $classScope = $this->classParser->parse($className);
        $methodScope = $classScope->method($methodName);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" in class "%s"', $methodName, $className),
            );
        }

        if (!($methodScope instanceof ClassMethodScope)) {
            throw new RuntimeException(
                sprintf('Cannot inspect return types on non-concrete method "%s"', $methodName),
            );
        }

        $resolver = $classScope->resolver()
            ->setVariableResolver(VariableResolver::create($methodScope->parameters()));

        $nodeFinder = new NodeFinder();

        /**
         * @var Return_[] $returnNodes
         */
        $returnNodes = $nodeFinder->findInstanceOf($methodScope->node(), Return_::class);

        /**
         * @var TypeContract[] $types
         */
        $types = [];

        foreach ($returnNodes as $returnNode) {
            if (!$returnNode->expr) {
                $types[] = new Types\UntypedType();
                continue;
            }

            $context = new ConverterContext($resolver);

            if ($returnNode->expr instanceof Array_) {
                $arrayProperties = collect();

                foreach ($returnNode->expr->items as $item) {
                    if (!$item) {
                        throw new RuntimeException('Unexpected null item in resource');
                    }

                    $key = $item->key;
                    if (!($key instanceof String_)) {
                        throw new RuntimeException('Unexpected non-string key in resource');
                    }

                    $type = $this->expressionTypeConverter->convert($item->value, $context);

                    if ($type instanceof Types\ClassType) {
                        $returnClass = $this->classParser->parse($type->fullyQualifiedName());

                        if (!$returnClass->hasParent(Resource::class)) {
                            throw new RuntimeException(
                                sprintf(
                                    'Unexpected non-resource class return "%s" in resource for property "%s"',
                                    $type->fullyQualifiedName(),
                                    $key->value,
                                ),
                            );
                        }

                        $format = $context->formatMethod()
                            ?? $this->findDefaultFormat($returnClass);
                        if (!$format) {
                            throw new RuntimeException(
                                sprintf(
                                    'Unable to determine format for resource class "%s" in resource for property "%s"',
                                    $type->fullyQualifiedName(),
                                    $key->value,
                                ),
                            );
                        }

                        $type = $this->parse($type->fullyQualifiedName(), $format);
                    }

                    $arrayProperties->put($key->value, $type);
                }

                $types[] = new Types\ArrayWithPropertiesType($arrayProperties);
            } else {
                $types[] = $this->expressionTypeConverter->convert($returnNode->expr, $context);
            }
        }

        $types = $this->combineArrayReturns($types);

        if (count($types) === 0) {
            return new Types\UntypedType();
        }

        if (count($types) === 1) {
            return $types[0];
        }

        return new Types\UnionType(...$types);
    }

    /**
     * @param array<int, TypeContract> $types
     * @return array<int, TypeContract>
     */
    private function combineArrayReturns(array $types): array
    {
        $types = collect($types);

        $arrayTypes = $types->filter(
            fn(TypeContract $type) => $type instanceof Types\ArrayWithPropertiesType,
        );
        $otherTypes = $types->filter(
            fn(TypeContract $type) => !($type instanceof Types\ArrayWithPropertiesType),
        );

        $arrayReturns = $arrayTypes->map(
            fn(Types\ArrayWithPropertiesType $type) => $type->properties(),
        );
        /**
         * @var Collection<int, string> $arrayKeys
         */
        $arrayKeys = $arrayReturns->flatMap(fn(Collection $properties) => $properties->keys()->toArray())->unique();

        /**
         * @var Collection<string, TypeContract> $mergedArrays
         */
        $mergedArrays = $arrayKeys->mapWithKeys(function (string $key) use ($arrayReturns) {
            /**
             * @var Collection<int, TypeContract> $types
             */
            $types = $arrayReturns->map(
                fn(Collection $properties) => $properties->get($key) ?? new Types\UndefinedType(),
            );

            /**
             * @var array<int, TypeContract> $splitTypes
             */
            $splitTypes = $types
                ->map(function (TypeContract $type) {
                    if ($type instanceof Types\UnionType) {
                        return $type->types()->all();
                    } else {
                        return [$type];
                    }
                })
                ->flatten()
                ->values()
                ->toArray();

            if (count($splitTypes) > 1) {
                $type = new Types\UnionType(...$splitTypes);
            } else {
                $type = $splitTypes[0];
            }

            return [$key => $type];
        });

        return $otherTypes
            ->merge([new Types\ArrayWithPropertiesType($mergedArrays)])
            ->values()
            ->all();
    }

    private function findDefaultFormat(ClassScopeContract $resourceClass): string|null
    {
        foreach ($resourceClass->methods() as $methodName => $methodScope) {
            $attribute = $methodScope->attribute(IsDefault::class);
            if ($attribute) {
                return $methodName;
            }
        }

        return null;
    }
}
