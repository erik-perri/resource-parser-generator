<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\PhpParser;

use PHPUnit\Framework\Attributes\CoversClass;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnParser;
use ResourceParserGenerator\Tests\Examples\Models\User;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassMethodReturnParser::class)]
class ClassMethodReturnParserTest extends TestCase
{
    public function testParsesClassMethodReturn(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Examples/Models/User.php';

        /** @var ClassMethodReturnParser $parser */
        $parser = $this->app->make(ClassMethodReturnParser::class);

        // Act
        $result = $parser->parse(
            [
                'getRouteKey',
                'typedMethod',
                'typedUnionMethod',
                'untypedWithDocMethod',
                'untypedWithoutDocMethod',
            ],
            User::class,
            $classFile,
        );

        // Assert
        $this->assertEquals(
            [
                'getRouteKey' => ['string'],
                'typedMethod' => ['int'],
                'typedUnionMethod' => ['string', 'int'],
                'untypedWithDocMethod' => ['string'],
                'untypedWithoutDocMethod' => ['mixed', 'void'],
            ],
            $result->methods,
        );
    }
}
