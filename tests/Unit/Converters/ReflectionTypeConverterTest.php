<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Converters;

use Closure;
use Mockery\CompositeExpectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionNamedType;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ReflectionTypeConverter::class)]
class ReflectionTypeConverterTest extends TestCase
{
    #[DataProvider('expectedParseResultsProvider')]
    public function testParsesTypeAsExpected(Closure $inputFactory, string $expectedOutput): void
    {
        // Arrange
        $input = $inputFactory->call($this);
        if ($input instanceof CompositeExpectation) {
            $input = $input->getMock();
        }

        // Act
        $result = $this->make(ReflectionTypeConverter::class)->convert($input);

        // Assert
        $this->assertSame($expectedOutput, $result->describe());
    }

    public static function expectedParseResultsProvider(): array
    {
        return [
            'array' => [
                'inputFactory' => fn() => $this->mock(ReflectionNamedType::class)
                    ->expects('getName')
                    ->andReturn('array'),
                'expectedOutput' => 'array',
            ],
            'untyped' => [
                'inputFactory' => fn() => null,
                'expectedOutput' => 'untyped',
            ],
        ];
    }
}
