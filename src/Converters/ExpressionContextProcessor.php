<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters;

use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Types;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

// TODO Find a better location for this class
class ExpressionContextProcessor
{
    public function __construct(
        private readonly ClassParserContract $classParser,
    ) {
        //
    }

    public function process(TypeContract $type, ConverterContext $context): TypeContract
    {
        $type = $this->processClassToClassWithMethod($type, $context);

        if ($context->isCollection()) {
            $type = new Types\ArrayType(null, $type);

            $context->setIsCollection(false);
        }

        return $type;
    }

    private function processClassToClassWithMethod(TypeContract $type, ConverterContext $context): TypeContract
    {
        if ($type instanceof Types\ClassWithMethodType) {
            return $type;
        }

        if ($type instanceof Types\UnionType && $type->isNullable()) {
            $nonNullType = $type->removeNullable();
            if ($nonNullType instanceof Types\ClassType) {
                return new Types\UnionType(
                    new Types\NullType(),
                    $this->processClassToClassWithMethod($nonNullType, $context),
                );
            }
        }

        if (!($type instanceof Types\ClassType)) {
            return $type;
        }

        if ($context->formatMethod()) {
            $typeWithMethod = new Types\ClassWithMethodType(
                $type->fullyQualifiedName(),
                $type->alias(),
                $context->formatMethod(),
            );

            $context->setFormatMethod(null);

            return $typeWithMethod;
        }

        $defaultFormat = $this->findDefaultFormat($type);
        if ($defaultFormat) {
            return new Types\ClassWithMethodType(
                $type->fullyQualifiedName(),
                $type->alias(),
                $defaultFormat,
            );
        }

        return $type;
    }

    private function findDefaultFormat(Types\ClassType $type): string|null
    {
        $resourceClass = $this->classParser->parse($type->fullyQualifiedName());
        if (!$resourceClass->hasParent(Resource::class)) {
            return null;
        }

        foreach ($resourceClass->methods() as $methodName => $methodScope) {
            $attribute = $methodScope->attribute(IsDefault::class);
            if ($attribute) {
                return $methodName;
            }
        }

        return null;
    }
}
