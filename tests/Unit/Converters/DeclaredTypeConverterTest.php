<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Converters;

use Closure;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Types;

#[CoversClass(DeclaredTypeConverter::class)]
class DeclaredTypeConverterTest extends TestCase
{
    #[DataProvider('identifierProvider')]
    #[DataProvider('intersectionProvider')]
    #[DataProvider('nullableProvider')]
    #[DataProvider('unionProvider')]
    public function testParses(mixed $input, TypeContract $expected, ?Closure $resolveMockFactory = null): void
    {
        // Arrange
        $parser = $this->make(DeclaredTypeConverter::class);

        /**
         * @var ResolverContract $resolveMock
         */
        $resolveMock = $resolveMockFactory
            ? $resolveMockFactory->call($this)
            : $this->mock(ResolverContract::class)
                ->shouldReceive('resolveClass')
                ->never()
                ->andReturnNull()
                ->getMock();

        // Act
        $result = $parser->convert($input, $resolveMock);

        // Assert
        $this->assertInstanceOf(get_class($expected), $result);
        $this->assertEquals($expected->name(), $result->name());
    }

    public static function identifierProvider(): array
    {
        return [
            'array without type' => [
                'input' => new Node\Identifier('array'),
                'expected' => new Types\ArrayType(null, null),
            ],
            'bool' => [
                'input' => new Node\Identifier('bool'),
                'expected' => new Types\BoolType(),
            ],
            'float' => [
                'input' => new Node\Identifier('float'),
                'expected' => new Types\FloatType(),
            ],
            'int' => [
                'input' => new Node\Identifier('int'),
                'expected' => new Types\IntType(),
            ],
            'mixed' => [
                'input' => new Node\Identifier('mixed'),
                'expected' => new Types\MixedType(),
            ],
            'null' => [
                'input' => new Node\Identifier('null'),
                'expected' => new Types\NullType(),
            ],
            'object' => [
                'input' => new Node\Identifier('object'),
                'expected' => new Types\ObjectType(),
            ],
            'string' => [
                'input' => new Node\Identifier('string'),
                'expected' => new Types\StringType(),
            ],
            'untyped' => [
                'input' => null,
                'expected' => new Types\UntypedType(),
            ],
            'void' => [
                'input' => new Node\Identifier('void'),
                'expected' => new Types\VoidType(),
            ],
            'class fully qualified' => [
                'input' => new Node\Name\FullyQualified('App\Foo\Bar'),
                'expected' => new Types\ClassType('App\Foo\Bar', null),
            ],
            'class relative' => [
                'input' => new Node\Name\Relative('Foo\Baz'),
                'expected' => new Types\ClassType('App\Foo\Baz', 'Foo\Baz'),
                'resolveMock' => fn() => $this->mock(ResolverContract::class)
                    ->shouldReceive('resolveClass')
                    ->once()
                    ->with('Foo\Baz')
                    ->andReturn('App\Foo\Baz')
                    ->getMock(),
            ],
            'class not qualified' => [
                'input' => new Node\Name('Baz'),
                'expected' => new Types\ClassType('App\Foo\Baz', 'Baz'),
                'resolveMock' => fn() => $this->mock(ResolverContract::class)
                    ->shouldReceive('resolveClass')
                    ->once()
                    ->with('Baz')
                    ->andReturn('App\Foo\Baz')
                    ->getMock(),
            ],
        ];
    }

    public static function unionProvider(): array
    {
        return [
            'int|string' => [
                'input' => new Node\UnionType([
                    new Node\Identifier('int'),
                    new Node\Identifier('string'),
                ]),
                'expected' => new Types\UnionType(
                    new Types\IntType(),
                    new Types\StringType(),
                ),
            ],
        ];
    }

    public static function nullableProvider(): array
    {
        return [
            'int' => [
                'input' => new Node\NullableType(new Node\Identifier('int')),
                'expected' => new Types\UnionType(new Types\NullType(), new Types\IntType()),
            ],
        ];
    }

    public static function intersectionProvider(): array
    {
        return [
            'Countable&Iterable' => [
                'input' => new Node\IntersectionType([
                    new Node\Name\FullyQualified('Countable'),
                    new Node\Name\FullyQualified('Iterable')
                ]),
                'expected' => new Types\IntersectionType(
                    new Types\ClassType('Countable', 'Countable'),
                    new Types\ClassType('Iterable', 'Iterable'),
                ),
            ],
        ];
    }
}
