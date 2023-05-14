<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\ExpressionTypeConverter;
use RuntimeException;

class ClassConstant implements ClassConstantContract
{
    public function __construct(
        private readonly Const_ $constant,
        private readonly ResolverContract $resolver,
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
        return ExpressionTypeConverter::create($this->resolver, null)->convert($this->constant->value);
    }

    public function value(): mixed
    {
        $value = $this->constant->value;

        if ($value instanceof String_) {
            return $value->value;
        }

        throw new RuntimeException(sprintf('Unhandled constant value type "%s"', get_class($value)));
    }
}