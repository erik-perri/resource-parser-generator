<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Examples\Nesting;

use ResourceParserGenerator\Tests\Examples\Nesting\Deeper;

class ClassA extends Deeper\ClassZ
{
    public readonly int $variableA;
}
