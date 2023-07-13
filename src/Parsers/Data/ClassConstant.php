<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contexts\ConverterContext;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

class ClassConstant implements ClassConstantContract
{
    public function __construct(
        private readonly Const_ $constant,
        private readonly ResolverContract $resolver,
        private readonly ExpressionTypeConverterContract $expressionTypeConverter,
        private readonly ExpressionValueParserContract $expressionValueParser,
    ) {
        //
    }

    public static function create(Const_ $constant, ResolverContract $resolver): self
    {
        return resolve(self::class, [
            'constant' => $constant,
            'resolver' => $resolver,
        ]);
    }

    public function name(): string
    {
        return $this->constant->name->toString();
    }

    public function type(): TypeContract
    {
        return $this->expressionTypeConverter->convert(
            $this->constant->value,
            ConverterContext::create($this->resolver),
        );
    }

    public function value(): mixed
    {
        return $this->expressionValueParser->parse($this->constant->value, $this->resolver);
    }
}
