<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface DocBlockTypeConverterContract
{
    public function convert(TypeNode $type, ResolverContract $resolver): TypeContract;
}
