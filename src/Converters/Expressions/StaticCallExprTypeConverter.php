<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\AnonymousResourceCollection;
use Sourcetoad\EnhancedResources\Resource;

class StaticCallExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ClassParserContract $classParser,
        private readonly DeclaredTypeConverterContract $declaredTypeConverter,
    ) {
        //
    }

    public function convert(StaticCall $expr, ConverterContext $context): TypeContract
    {
        $methodName = $expr->name;
        if ($methodName instanceof Expr) {
            throw new RuntimeException('Static call name is not a string');
        }

        if ($expr->class instanceof Expr) {
            throw new RuntimeException('Static call class is not a string');
        }

        $classType = $this->declaredTypeConverter->convert($expr->class, $context->resolver());
        if (!($classType instanceof Types\ClassType)) {
            throw new RuntimeException('Static call class is not a class type');
        }

        $classScope = $this->classParser->parse($classType->fullyQualifiedName());
        $methodScope = $classScope->method($methodName->name);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" in "%s"', $methodName->name, $classScope->name()),
            );
        }

        // When we encounter a `::collection` call on a resource, we convert the type to the resource flag the context
        // as actually being a collection.  This allows us to hook into the existing format handling for loading the
        // collection format.  It then gets converted into an array of the resource by the context processor.
        // TODO Figure out a cleaner approach.
        $methodReturn = $methodScope->returnType();
        if ($classScope->hasParent(Resource::class) &&
            $methodName->name === 'collection' &&
            $methodReturn instanceof Types\ClassType
        ) {
            $isAnonymousResource = $methodReturn->fullyQualifiedName() === AnonymousResourceCollection::class
                || $this->classParser->parse($methodReturn->fullyQualifiedName())
                    ->hasParent(AnonymousResourceCollection::class);
            if ($isAnonymousResource) {
                $context->setIsCollection(true);

                return new Types\ClassType($classType->fullyQualifiedName(), $classType->alias());
            }
        }

        return $methodScope->returnType();
    }
}
