<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\String_;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\ResolveScope;
use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;
use ResourceParserGenerator\Tests\TestCase;

/**
 * @covers DocBlockTagTypeConverter
 * @covers ResourceParserGeneratorServiceProvider
 */
class DocBlockTagTypeConverterTest extends TestCase
{
    #[DataProvider('convertsTypeProvider')]
    public function testConvertsType(Type $type, string|array $expectedResult): void
    {
        // Arrange
        $scope = $this->createMock(ResolveScope::class);
        $scope->method('resolveClass')->willReturnArgument(0);

        $converter = new DocBlockTagTypeConverter();

        // Act
        $result = $converter->convert($type, $scope);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public static function convertsTypeProvider(): array
    {
        return [
            'string' => [
                'type' => new String_(),
                'expectedResult' => 'string',
            ],
            'compound' => [
                'type' => new Compound([new String_(), new Integer()]),
                'expectedResult' => ['string', 'int'],
            ],
        ];
    }
}
