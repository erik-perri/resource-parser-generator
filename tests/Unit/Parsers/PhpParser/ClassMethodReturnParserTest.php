<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\PhpParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnParser;
use ResourceParserGenerator\Parsers\PhpParser\UseStatementParser;
use ResourceParserGenerator\Parsers\ResolveScope;
use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassMethodReturnParser::class)]
#[UsesClass(ClassFileTypehintParser::class)]
#[UsesClass(ClassTypehints::class)]
#[UsesClass(DocBlockTagTypeConverter::class)]
#[UsesClass(ResolveScope::class)]
#[UsesClass(ResourceParserGeneratorServiceProvider::class)]
#[UsesClass(UseStatementParser::class)]
class ClassMethodReturnParserTest extends TestCase
{
    public function testParsesClassMethodReturn(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Stubs/Models/User.php';

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
