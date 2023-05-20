<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use Illuminate\Http\Resources\MissingValue;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ClassConstFetchValueParserContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\Traits\ParsesFetchSides;
use ResourceParserGenerator\Types\ClassType;
use ResourceParserGenerator\Types\MixedType;
use ResourceParserGenerator\Types\NullType;
use ResourceParserGenerator\Types\UndefinedType;
use ResourceParserGenerator\Types\UnionType;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Resource;

class MethodCallExprTypeConverter implements ExprTypeConverterContract
{
    use ParsesFetchSides;

    public function __construct(
        private readonly ClassConstFetchValueParserContract $classConstFetchValueParser,
        private readonly ClassParserContract $classParser,
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
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

        $methodScope = $leftSide->method($rightSide);
        if (!$methodScope) {
            throw new RuntimeException(sprintf('Unknown method "%s" in "%s"', $rightSide, $leftSide->name()));
        }

        $type = $methodScope->returnType();

        if ($rightSide === 'whenLoaded') {
            $type = $this->handleWhenLoaded($expr, $context, $type);
        }

        if ($leftSide->hasParent(Resource::class) && $rightSide === 'format') {
            $this->handleFormat($expr, $context, $leftSide);
        }

        if ($expr instanceof NullsafeMethodCall) {
            if ($type instanceof UnionType) {
                $type = $type->addToUnion(new NullType());
            } else {
                $type = new UnionType($type, new NullType());
            }
        }

        return $type;
    }

    private function handleWhenLoaded(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        TypeContract $type,
    ): TypeContract {
        if (!($type instanceof UnionType)) {
            throw new RuntimeException(
                sprintf('Unexpected non-union whenLoaded method return, found "%s"', $type->describe()),
            );
        }

        $args = $expr->getArgs();
        if (count($args) < 2) {
            throw new RuntimeException('Unhandled missing second argument for whenLoaded');
        }

        $loadedProperty = $args[0]->value;
        if (!($loadedProperty instanceof String_)) {
            throw new RuntimeException('Unhandled non-string first argument for whenLoaded');
        }
        $loadedProperty = $loadedProperty->value;

        $returnWhenLoaded = $this->expressionTypeConverter->convert(
            $args[1]->value,
            (new ConverterContext($context->resolver(), [$loadedProperty]))
        );

        $returnWhenUnloaded = count($args) > 2
            ? $this->expressionTypeConverter->convert($args[2]->value, $context)
            : new UndefinedType();

        return $type
            ->addToUnion($returnWhenLoaded)
            ->addToUnion($returnWhenUnloaded)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof MixedType)
            ->removeFromUnion(fn(TypeContract $type) => $type instanceof ClassType
                && $type->fullyQualifiedName() === MissingValue::class);
    }

    private function handleFormat(
        MethodCall|NullsafeMethodCall $expr,
        ConverterContext $context,
        ClassScopeContract $classScope,
    ): void {
        $formatName = null;
        $formatArg = $expr->getArgs()[0]->value;

        if ($formatArg instanceof String_) {
            $formatName = $formatArg->value;
        } elseif ($formatArg instanceof ClassConstFetch) {
            $formatName = $this->classConstFetchValueParser->parse($formatArg, $context->resolver());

            if (!is_string($formatName)) {
                throw new RuntimeException('Format name is not a string');
            }
        }

        if ($formatName) {
            foreach ($classScope->methods() as $methodName => $methodScope) {
                $attribute = $methodScope->attribute(Format::class);
                if ($attribute && $attribute->argument(0) === $formatName) {
                    $context->setFormatMethod($methodName);
                }
            }
        }
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
