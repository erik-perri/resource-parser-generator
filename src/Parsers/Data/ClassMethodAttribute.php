<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\Data;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use ResourceParserGenerator\Contracts\AttributeContract;
use ResourceParserGenerator\Contracts\Parsers\ClassConstFetchValueParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use RuntimeException;

class ClassMethodAttribute implements AttributeContract
{
    public function __construct(
        private readonly Attribute $attribute,
        private readonly ResolverContract $resolver,
        private readonly ClassConstFetchValueParserContract $classConstFetchValueParser,
    ) {
        //
    }

    public static function create(
        Attribute $attribute,
        ResolverContract $resolver
    ): self {
        return resolve(self::class, [
            'attribute' => $attribute,
            'resolver' => $resolver,
        ]);
    }

    public function argument(int $index): mixed
    {
        $value = $this->attribute->args[$index]->value;

        if ($value instanceof String_) {
            return $value->value;
        }

        if ($value instanceof ClassConstFetch) {
            return $this->classConstFetchValueParser->parse($value, $this->resolver);
        }

        throw new RuntimeException(
            sprintf('Unhandled argument type of "%s" for index %d', get_class($value), $index),
        );
    }
}
