<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use PhpParser\Node\Attribute;
use ResourceParserGenerator\Contracts\AttributeContract;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use RuntimeException;
use Throwable;

class ClassMethodAttribute implements AttributeContract
{
    public function __construct(
        private readonly Attribute $attribute,
        private readonly ResolverContract $resolver,
        private readonly ExpressionValueParserContract $expressionValueParser,
    ) {
        //
    }

    public static function create(
        Attribute $attribute,
        ResolverContract $resolver,
    ): self {
        return resolve(self::class, [
            'attribute' => $attribute,
            'resolver' => $resolver,
        ]);
    }

    public function argument(int $index): mixed
    {
        $value = $this->attribute->args[$index]->value;

        try {
            return $this->expressionValueParser->parse($value, $this->resolver);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Unhandled argument type of "%s" for index %d', get_class($value), $index),
                0,
                $exception,
            );
        }
    }
}
