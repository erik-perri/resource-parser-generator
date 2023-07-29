<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFormatLocatorContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\ResourceFormat;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\AnonymousResourceCollection;
use Sourcetoad\EnhancedResources\Resource;

class StaticCallExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ClassParserContract $classParser,
        private readonly DeclaredTypeConverterContract $declaredTypeConverter,
        private readonly ResourceFormatLocatorContract $resourceFileFormatLocator,
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

        $classScope = $this->classParser->parseType($classType);
        $methodScope = $classScope->method($methodName->name);
        if (!$methodScope) {
            throw new RuntimeException(
                sprintf('Unknown method "%s" in "%s"', $methodName->name, $classScope->name()),
            );
        }

        $methodReturn = $methodScope->returnType();
        if ($classScope->hasParent(Resource::class) && $methodReturn instanceof Types\ClassType) {
            if ($methodName->name === 'collection' && $this->isAnonymousResource($methodReturn)) {
                return new Types\ResourceType(
                    $classType->fullyQualifiedName(),
                    $classType->alias(),
                    $this->resourceFileFormatLocator->formatsInClass($classScope)
                        ->first(fn(ResourceFormat $format) => $format->isDefault),
                    true,
                );
            } elseif ($methodName->name === 'make' && $this->isResource($methodReturn)) {
                return new Types\ResourceType(
                    $classType->fullyQualifiedName(),
                    $classType->alias(),
                    $this->resourceFileFormatLocator->formatsInClass($classScope)
                        ->first(fn(ResourceFormat $format) => $format->isDefault),
                    false,
                );
            }
        }

        return $methodScope->returnType();
    }

    private function isAnonymousResource(Types\ClassType $type): bool
    {
        return $type->fullyQualifiedName() === AnonymousResourceCollection::class
            || $this->classParser->parseType($type)->hasParent(AnonymousResourceCollection::class);
    }

    private function isResource(Types\ClassType $methodReturn): bool
    {
        return $methodReturn->fullyQualifiedName() === Resource::class
            || $this->classParser->parseType($methodReturn)->hasParent(Resource::class);
    }
}
