<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\String_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\ResolveScope;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DocBlockTagTypeConverter::class)]
class DocBlockTagTypeConverterTest extends TestCase
{
    #[DataProvider('convertsTypeProvider')]
    public function testConvertsType(Type $type, string|array $expectedResult): void
    {
        // Arrange
        /** @var ResolveScope $scope */
        $scope = $this->mock(ResolveScope::class)
            ->expects('resolveClass')
            ->atLeast()
            ->once()
            ->andReturnArg(0)
            ->getMock();

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
                'expectedResult' => ['string'],
            ],
            'compound' => [
                'type' => new Compound([new String_(), new Integer()]),
                'expectedResult' => ['string', 'int'],
            ],
        ];
    }
}
