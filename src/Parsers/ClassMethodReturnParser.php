<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Converters\ExpressionTypeConverter;
use ResourceParserGenerator\Parsers\DataObjects\ClassMethodScope;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use ResourceParserGenerator\Resolvers\Resolver;
use ResourceParserGenerator\Resolvers\VariableResolver;
use ResourceParserGenerator\Types;
use RuntimeException;

class ClassMethodReturnParser
{
    public function __construct(
        private readonly ClassFileLocatorContract $classFileLocator,
        private readonly PhpFileParser $phpFileParser,
        private readonly ExpressionTypeConverter $expressionTypeConverter,
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
        $classFile = $this->classFileLocator->get($className);
        $fileScope = $this->phpFileParser->parse(File::get($classFile));
        $classScope = $fileScope->class(class_basename($className));
        $methodScope = $classScope->method($methodName);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s"', $methodName),
            );
        }

        if (!($methodScope instanceof ClassMethodScope)) {
            throw new RuntimeException(
                sprintf('Cannot inspect return types on non-concrete method "%s"', $methodName),
            );
        }

        $classResolver = ClassNameResolver::create($fileScope);
        $resolver = Resolver::create(
            $classResolver,
            VariableResolver::create($methodScope->parameters()),
            $className,
        );

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

                    $arrayProperties->put(
                        $key->value,
                        $this->expressionTypeConverter->convert($item->value, $resolver),
                    );
                }

                $types[] = new Types\ArrayWithPropertiesType($arrayProperties);
            } else {
                $types[] = $this->expressionTypeConverter->convert($returnNode->expr, $resolver);
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
}
