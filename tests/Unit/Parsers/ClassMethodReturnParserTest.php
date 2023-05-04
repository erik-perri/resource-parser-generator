<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\ClassMethodReturnParser;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Types\ArrayWithPropertiesType;

#[CoversClass(ClassMethodReturnParser::class)]
class ClassMethodReturnParserTest extends TestCase
{
    #[DataProvider('methodReturnProvider')]
    public function testParsesClassMethodReturn(string $className, string $methodName, array|string $expected): void
    {
        // Arrange
        $parser = $this->make(ClassMethodReturnParser::class);

        // Act
        $result = $parser->parse($className, $methodName);

        if ($result instanceof ArrayWithPropertiesType) {
            $result = $result->properties()->map(fn($property) => $property->name())->toArray();
        } else {
            $result = $result->name();
        }

        // Assert
        $this->assertEquals($expected, $result);
    }

    public static function methodReturnProvider(): array
    {
        return [
            class_basename(UserResource::class) . '::adminList' => [
                'className' => UserResource::class,
                'methodName' => 'adminList',
                'expected' => [
                    'id' => 'string',
                    'name' => 'string',
                    'email' => 'string',
                    'created_at' => 'string|null',
                    'updated_at' => 'string|null',
                ],
            ],
        ];
    }
}
