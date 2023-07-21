<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Converters\Expressions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contexts\ConverterContextProcessor;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\Expressions\ExprTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Resolvers\VariableResolver;
use ResourceParserGenerator\Types\UntypedType;
use RuntimeException;

class ArrowFunctionExprTypeConverter implements ExprTypeConverterContract
{
    public function __construct(
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly ConverterContextProcessor $contextProcessor,
        private readonly DeclaredTypeConverterContract $declaredTypeConverter,
    ) {
        //
    }

    public function convert(ArrowFunction $expr, ConverterContext $context): TypeContract
    {
        $variableResolver = $this->createVariableResolver($expr, $context);

        $childContext = ConverterContext::create(
            $context->resolver()->setVariableResolver($variableResolver),
            $context->nonNullProperties(),
        );
        $type = $this->expressionTypeConverter->convert($expr->expr, $childContext);

        return $this->contextProcessor->process($type, $childContext);
    }

    private function createVariableResolver(ArrowFunction $expr, ConverterContext $context): VariableResolver
    {
        $convertedParams = collect($expr->params)
            ->mapWithKeys(function ($param) use ($context) {
                if (!($param instanceof Param)) {
                    throw new RuntimeException(sprintf('Unhandled param type "%s"', get_class($param)));
                }

                if (!($param->var instanceof Variable)) {
                    throw new RuntimeException(sprintf('Unhandled non-variable var "%s"', get_class($param->var)));
                }

                if ($param->var->name instanceof Expr) {
                    throw new RuntimeException('Variable name is not a string');
                }

                $declaredType = $param->type
                    ? $this->declaredTypeConverter->convert($param->type, $context->resolver())
                    : (new UntypedType())
                        ->setComment(sprintf('No declared type on parameter %s', $param->var->name));

                return [
                    $param->var->name => $declaredType,
                ];
            });

        return VariableResolver::create($convertedParams);
    }
}
