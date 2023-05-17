<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Converters;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Converters\VariableTypeConverter;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(VariableTypeConverter::class)]
class VariableTypeConverterTest extends TestCase
{
    #[DataProvider('expectedParseResultsProvider')]
    public function testParsesTypeAsExpected($input, string $expectedOutput): void
    {
        // Arrange
        // Nothing to arrange.

        // Act
        $result = $this->make(VariableTypeConverter::class)->convert($input);

        // Assert
        $this->assertSame($expectedOutput, $result->describe());
    }

    public static function expectedParseResultsProvider(): array
    {
        return [
            'boolean' => [true, 'bool'],
            'integer' => [1, 'int'],
            'double' => [1.1, 'float'],
            'string' => ['string', 'string'],
            'array' => [[], 'array'],
            'object' => [
                new class {
                },
                'object'
            ],
            'resource' => [fopen('php://memory', 'r'), 'resource'],
            'resource (closed)' => [tap(fopen('php://memory', 'r'), fn($resource) => fclose($resource)), 'resource'],
            'NULL' => [null, 'null'],
        ];
    }
}
