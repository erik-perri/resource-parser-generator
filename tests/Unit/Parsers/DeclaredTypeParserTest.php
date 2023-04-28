<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use Closure;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\DeclaredTypeParser;
use ResourceParserGenerator\Parsers\Types\ArrayType;
use ResourceParserGenerator\Parsers\Types\BoolType;
use ResourceParserGenerator\Parsers\Types\ClassType;
use ResourceParserGenerator\Parsers\Types\FloatType;
use ResourceParserGenerator\Parsers\Types\IntType;
use ResourceParserGenerator\Parsers\Types\MixedType;
use ResourceParserGenerator\Parsers\Types\NullType;
use ResourceParserGenerator\Parsers\Types\ObjectType;
use ResourceParserGenerator\Parsers\Types\StringType;
use ResourceParserGenerator\Parsers\Types\UnionType;
use ResourceParserGenerator\Parsers\Types\UntypedType;
use ResourceParserGenerator\Parsers\Types\VoidType;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DeclaredTypeParser::class)]
class DeclaredTypeParserTest extends TestCase
{
    #[DataProvider('identifierProvider')]
    #[DataProvider('nullableProvider')]
    #[DataProvider('unionProvider')]
    public function testParses(mixed $input, TypeContract $expected, ?Closure $resolveMockFactory = null): void
    {
        // Arrange
        $parser = $this->make(DeclaredTypeParser::class);

        /**
         * @var ClassNameResolverContract $resolveMock
         */
        $resolveMock = $resolveMockFactory
            ? $resolveMockFactory->call($this)
            : $this->mock(ClassNameResolverContract::class)
                ->shouldReceive('resolve')
                ->never()
                ->andReturnNull()
                ->getMock();

        // Act
        $result = $parser->parse($input, $resolveMock);

        // Assert
        $this->assertInstanceOf(get_class($expected), $result);
        $this->assertEquals($expected->name(), $result->name());
    }

    public static function identifierProvider(): array
    {
        return [
            'array without type' => [
                'input' => new Node\Identifier('array'),
                'expected' => new ArrayType(null),
            ],
            'bool' => [
                'input' => new Node\Identifier('bool'),
                'expected' => new BoolType(),
            ],
            'float' => [
                'input' => new Node\Identifier('float'),
                'expected' => new FloatType(),
            ],
            'int' => [
                'input' => new Node\Identifier('int'),
                'expected' => new IntType(),
            ],
            'mixed' => [
                'input' => new Node\Identifier('mixed'),
                'expected' => new MixedType(),
            ],
            'null' => [
                'input' => new Node\Identifier('null'),
                'expected' => new NullType(),
            ],
            'object' => [
                'input' => new Node\Identifier('object'),
                'expected' => new ObjectType(),
            ],
            'string' => [
                'input' => new Node\Identifier('string'),
                'expected' => new StringType(),
            ],
            'untyped' => [
                'input' => null,
                'expected' => new UntypedType(),
            ],
            'void' => [
                'input' => new Node\Identifier('void'),
                'expected' => new VoidType(),
            ],
            'class fully qualified' => [
                'input' => new Node\Name\FullyQualified('App\Foo\Bar'),
                'expected' => new ClassType('App\Foo\Bar', null),
            ],
            'class relative' => [
                'input' => new Node\Name\Relative('Foo\Baz'),
                'expected' => new ClassType('App\Foo\Baz', 'Foo\Baz'),
                'resolveMock' => fn() => $this->mock(ClassNameResolverContract::class)
                    ->shouldReceive('resolve')
                    ->once()
                    ->with('Foo\Baz')
                    ->andReturn('App\Foo\Baz')
                    ->getMock(),
            ],
            'class not qualified' => [
                'input' => new Node\Name('Baz'),
                'expected' => new ClassType('App\Foo\Baz', 'Baz'),
                'resolveMock' => fn() => $this->mock(ClassNameResolverContract::class)
                    ->shouldReceive('resolve')
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
                'expected' => new UnionType(
                    new IntType(),
                    new StringType(),
                ),
            ],
        ];
    }

    public static function nullableProvider(): array
    {
        return [
            'int' => [
                'input' => new Node\NullableType(new Node\Identifier('int')),
                'expected' => new UnionType(new NullType(), new IntType()),
            ],
        ];
    }

    // TODO
    // public static function intersectionProvider(): array
    // {
    //     return [];
    // }
}
