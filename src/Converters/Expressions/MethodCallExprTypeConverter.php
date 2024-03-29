<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Traits\ParsesFetchSides;
use ResourceParserGenerator\DataObjects\ResourceFormat;
use ResourceParserGenerator\Filesystem\ResourceFormatLocator;
use ResourceParserGenerator\Parsers\Data\ClassScope;
use ResourceParserGenerator\Types;
use RuntimeException;
use Sourcetoad\EnhancedResources\Resource;

class MethodCallExprTypeConverter implements ExprTypeConverterContract
{
    use ParsesFetchSides;

    public function __construct(
        private readonly ArrowFunctionExprTypeConverter $arrowFunctionExprTypeConverter,
        private readonly ClassParserContract $classParser,
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly ExpressionValueParserContract $expressionValueParser,
        private readonly NodeFinder $nodeFinder,
        private readonly ResourceFormatLocator $resourceFileFormatLocator,
    ) {
        //
    }

    public function convert(MethodCall|NullsafeMethodCall $expr, ConverterContext $context): TypeContract
    {
        $leftSide = $this->convertLeftSideToClassScope($expr, $context);
        $rightSide = $this->convertRightSide($expr, $context);

        if (!is_string($rightSide)) {
            throw new RuntimeException('Right side of method call is not a string');
        }

        if ($leftSide->fullyQualifiedName() === Collection::class || $leftSide->hasParent(Collection::class)) {
            switch ($rightSide) {
                case 'all':
                    return $this->handleCollectionAll($leftSide);
                case 'map':
                    return $this->handleCollectionMap($expr, $context, $leftSide);
                case 'pluck':
                    return $this->handleCollectionPluck($expr, $context, $leftSide);
                default:
                    break;
            }
        }

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(sprintf('Unknown method "%s" in "%s"', $rightSide, $leftSide->name()));
        }

        $type = $methodScope->returnType();

        if ($rightSide === 'when') {
            $type = $this->handleWhen($expr, $context, $type);
        } elseif ($rightSide === 'whenLoaded') {
            $type = $this->handleWhenLoaded($expr, $context, $type);
        }

        if ($leftSide->hasParent(Resource::class) && $rightSide === 'format') {
            $format = $this->getResourceFormat($expr, $context, $leftSide);

            if ($format) {
                $actualType = $this->convertLeftSideToType($expr, $context);
                if ($actualType instanceof Types\ResourceType) {
                    $type = new Types\ResourceType(
                        $actualType->fullyQualifiedName(),
                        $actualType->alias(),
                        $format,
                        $actualType->isCollection,
                    );
                } elseif ($actualType instanceof Types\ClassType) {
                    $type = new Types\ResourceType(
                        $actualType->fullyQualifiedName(),
                        $actualType->alias(),
                        $format,
                        false,
                    );
                }
            }
        }

        if ($expr instanceof NullsafeMethodCall) {
            if ($type instanceof Types\UnionType) {
                $type = $type->addToUnion(new Types\NullType());
            } else {
                $type = new Types\UnionType($type, new Types\NullType());
            }
        }

        return $type;
    }

    private function handleCollectionAll(ClassScopeContract $leftSide): TypeContract
    {
        if (!($leftSide instanceof ClassScope)) {
            throw new RuntimeException(sprintf(
                'Unexpected fake class type for collection "%s"',
                $leftSide->fullyQualifiedName(),
            ));
        }

        $generics = $leftSide->knownGenerics();
        if (!$generics?->count()) {
            throw new RuntimeException(sprintf(
                'Unexpected missing generics for collection "%s"',
                $leftSide->fullyQualifiedName(),
            ));
        }

        return new Types\ArrayType(null, $generics->last());
    }

    private function handleCollectionMap(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        ClassScopeContract $leftSide,
    ): TypeContract {
        $arguments = $expr->getArgs();
        if (!count($arguments)) {
            throw new RuntimeException('Unhandled missing first argument for map');
        }

        $callable = $arguments[0]->value;
        if (!($callable instanceof ArrowFunction)) {
            throw new RuntimeException('Unhandled non-arrow function for map');
        }

        $returnType = $this->arrowFunctionExprTypeConverter->convert($callable, $context);

        return new Types\ClassType(
            $leftSide->fullyQualifiedName(),
            null,
            collect([new Types\IntType(), $returnType]),
        );
    }

    private function handleCollectionPluck(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        ClassScopeContract $leftSide,
    ): TypeContract {
        $arguments = $expr->getArgs();
        if (!count($arguments)) {
            throw new RuntimeException('Unhandled missing first argument for pluck');
        }

        if (!($leftSide instanceof ClassScope)) {
            throw new RuntimeException(sprintf(
                'Unexpected fake class type for collection "%s"',
                $leftSide->fullyQualifiedName(),
            ));
        }

        $generics = $leftSide->knownGenerics();
        if (!$generics?->count()) {
            throw new RuntimeException(sprintf(
                'Unexpected missing generics for collection "%s"',
                $leftSide->fullyQualifiedName(),
            ));
        }

        $argument = reset($arguments)->value;
        $argument = $this->expressionValueParser->parse($argument, $context->resolver());

        if (!is_string($argument)) {
            throw new RuntimeException(
                sprintf('Unhandled non-string first argument for pluck "%s"', gettype($argument)),
            );
        }

        /**
         * @var TypeContract $pluckingFromType
         */
        $pluckingFromType = $generics->last();
        if (!($pluckingFromType instanceof Types\ClassType)) {
            throw new RuntimeException(sprintf(
                'Unexpected non-class type for pluck call "%s"',
                $pluckingFromType->describe(),
            ));
        }

        $pluckingFromScope = $this->classParser->parseType($pluckingFromType);
        $pluckedType = $pluckingFromScope->propertyType($argument);
        if (!$pluckedType) {
            throw new RuntimeException(sprintf(
                'Unknown type for property "%s" on "%s"',
                $argument,
                $pluckingFromScope->fullyQualifiedName(),
            ));
        }

        return new Types\ClassType(
            $leftSide->fullyQualifiedName(),
            null,
            collect([new Types\IntType(), $pluckedType]),
        );
    }

    private function getResourceFormat(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        ClassScopeContract $classScope,
    ): ?ResourceFormat {
        $formatArg = $expr->getArgs()[0]->value;
        $formatName = $this->expressionValueParser->parse($formatArg, $context->resolver());

        if (!is_string($formatName)) {
            throw new RuntimeException(sprintf('Unhandled non-string format name "%s"', gettype($formatArg)));
        }

        return $this->resourceFileFormatLocator->formatsInClass($classScope)
            ->first(fn(ResourceFormat $format) => $format->formatName === $formatName);
    }

    private function handleWhen(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        TypeContract $type,
    ): TypeContract {
        if (!($type instanceof Types\UnionType)) {
            throw new RuntimeException(
                sprintf('Unexpected non-union whenLoaded method return, found "%s"', $type->describe()),
            );
        }

        $args = $expr->getArgs();
        if (count($args) < 2) {
            throw new RuntimeException('Unhandled missing second argument for whenLoaded');
        }

        $returnWhenTrue = $this->expressionTypeConverter->convert(
            $args[1]->value,
            ConverterContext::create($context->resolver(), $this->findResourcePropertyAccesses($args[0])),
        );

        $returnWhenFalse = count($args) > 2
            ? $this->expressionTypeConverter->convert($args[2]->value, $context)
            : new Types\UndefinedType();

        return $type
            ->addToUnion($returnWhenTrue)
            ->addToUnion($returnWhenFalse)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\MixedType)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\ClassType
                && $type->fullyQualifiedName() === MissingValue::class);
    }

    private function handleWhenLoaded(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        TypeContract $type,
    ): TypeContract {
        if (!($type instanceof Types\UnionType)) {
            throw new RuntimeException(
                sprintf('Unexpected non-union whenLoaded method return, found "%s"', $type->describe()),
            );
        }

        $args = $expr->getArgs();
        if (count($args) < 2) {
            throw new RuntimeException('Unhandled missing second argument for whenLoaded');
        }

        $loadedProperty = $args[0]->value;
        $loadedProperty = $this->expressionValueParser->parse($loadedProperty, $context->resolver());

        if (!is_string($loadedProperty)) {
            throw new RuntimeException(
                sprintf('Unhandled non-string first argument for whenLoaded "%s"', gettype($loadedProperty)),
            );
        }

        $returnWhenLoaded = $this->expressionTypeConverter->convert(
            $args[1]->value,
            ConverterContext::create($context->resolver(), collect([$loadedProperty])),
        );

        $returnWhenUnloaded = count($args) > 2
            ? $this->expressionTypeConverter->convert($args[2]->value, $context)
            : new Types\UndefinedType();

        return $type
            ->addToUnion($returnWhenLoaded)
            ->addToUnion($returnWhenUnloaded)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\MixedType)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof Types\ClassType
                && $type->fullyQualifiedName() === MissingValue::class);
    }

    /**
     * @param NodeAbstract $node
     * @return Collection<int, string>
     */
    private function findResourcePropertyAccesses(NodeAbstract $node): Collection
    {
        $properties = collect();

        /**
         * @var PropertyFetch[] $fetches
         */
        $fetches = $this->nodeFinder->find($node, fn(NodeAbstract $node) => $node instanceof PropertyFetch);

        foreach ($fetches as $fetch) {
            $fetchingFrom = $fetch->var;

            if (!($fetch->name instanceof Identifier) ||
                !($fetchingFrom instanceof PropertyFetch) ||
                !($fetchingFrom->var instanceof Variable) ||
                !($fetchingFrom->name instanceof Identifier) ||
                $fetchingFrom->var->name !== 'this' ||
                $fetchingFrom->name->name !== 'resource') {
                continue;
            }

            $properties->add($fetch->name->name);
        }

        return $properties;
    }

    protected function expressionTypeConverter(): ExpressionTypeConverterContract
    {
        return $this->expressionTypeConverter;
    }

    protected function classParser(): ClassParserContract
    {
        return $this->classParser;
    }
}
