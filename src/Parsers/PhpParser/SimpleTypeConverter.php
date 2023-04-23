<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers\PhpParser;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use ResourceParserGenerator\Exceptions\ParseResultException;

class SimpleTypeConverter
{
    /**
     * @return string[]
     * @throws ParseResultException
     */
    public function convert(null|Identifier|Name|ComplexType $type): array
    {
        if ($type === null) {
            return ['mixed'];
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return [$this->convertTypeName($type)];
        }

        if ($type instanceof NullableType) {
            return array_unique(['null', $this->convertTypeName($type->type)]);
        }

        if ($type instanceof UnionType) {
            return array_unique(array_merge(...array_map(
                fn(null|Identifier|Name|ComplexType $type) => $this->convert($type),
                $type->types,
            )));
        }

        throw new ParseResultException('Unhandled parameter type' . $type->getType(), $type);
    }

    private function convertTypeName(Identifier|Name $type): string
    {
        if ($type instanceof Identifier) {
            return $type->name;
        }

        return $type->toString();
    }
}
