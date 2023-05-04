<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

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

        $classResolver = ClassNameResolver::create($fileScope);
        $resolver = Resolver::create($classResolver, $className);

        $nodeFinder = new NodeFinder();

        if (!($methodScope instanceof ClassMethodScope)) {
            throw new RuntimeException(
                sprintf('Cannot inspect return types on non-concrete method "%s"', $methodName),
            );
        }

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

        if (count($types) === 0) {
            return new Types\UntypedType();
        }

        if (count($types) === 1) {
            return $types[0];
        }

        return new Types\UnionType(...$types);
    }
}
