<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;
use phpDocumentor\Reflection\Types\Void_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\Context\ResolverContract;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DocBlockTagTypeConverter::class)]
class DocBlockTagTypeConverterTest extends TestCase
{
    #[DataProvider('convertsTypeProvider')]
    public function testConvertsType(?Type $type, array $expectedResult): void
    {
        // Arrange
        /**
         * @var ResolverContract $scope
         */
        $scope = $this->mock(ResolverContract::class)
            ->expects('resolveClass')
            ->zeroOrMoreTimes()
            ->andReturnUsing(fn(string $name) => 'Mock\\Namespace\\' . $name)
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
            'boolean' => [
                'type' => new Boolean(),
                'expectedResult' => ['bool'],
            ],
            'compound' => [
                'type' => new Compound([new String_(), new Integer()]),
                'expectedResult' => ['string', 'int'],
            ],
            'float' => [
                'type' => new Float_(),
                'expectedResult' => ['float'],
            ],
            'int' => [
                'type' => new Integer(),
                'expectedResult' => ['int'],
            ],
            'non scalar' => [
                'type' => new Object_(new Fqsen('\\Class')),
                'expectedResult' => ['Mock\\Namespace\\Class'],
            ],
            'null' => [
                'type' => new Null_(),
                'expectedResult' => ['null'],
            ],
            'nullable type' => [
                'type' => new Nullable(new String_()),
                'expectedResult' => ['null', 'string'],
            ],
            'string' => [
                'type' => new String_(),
                'expectedResult' => ['string'],
            ],
            'untyped' => [
                'type' => null,
                'expectedResult' => ['mixed'],
            ],
            'untyped array' => [
                'type' => new Array_(),
                'expectedResult' => ['array'],
            ],
            'void' => [
                'type' => new Void_(),
                'expectedResult' => ['void'],
            ],
        ];
    }
}
