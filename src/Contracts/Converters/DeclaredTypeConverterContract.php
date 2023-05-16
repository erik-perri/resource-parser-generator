<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Converters;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;

interface DeclaredTypeConverterContract
{
    public function convert(ComplexType|Identifier|Name|null $type, ResolverContract $resolver): TypeContract;
}
