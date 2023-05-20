<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Converters;

use Closure;
use Illuminate\Support\Collection;
use Mockery\CompositeExpectation;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Converters\DocBlockTypeConverter;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DocBlockTypeConverter::class)]
class DocBlockTypeConverterTest extends TestCase
{
    #[DataProvider('expectedParseResultsProvider')]
    public function testParsesTypeAsExpected(
        TypeNode $input,
        string $expectedOutput,
        ?Closure $resolverFactory = null
    ): void {
        // Arrange
        /**
         * @var ResolverContract $resolver
         */
        $resolver = $resolverFactory?->call($this) ?? $this->mock(ResolverContract::class);
        if ($resolver instanceof CompositeExpectation) {
            $resolver = $resolver->getMock();
        }

        // Act
        $result = $this->make(DocBlockTypeConverter::class)->convert($input, $resolver);

        // Assert
        $this->assertSame($expectedOutput, $result->describe());
    }

    public static function expectedParseResultsProvider(): array
    {
        return [
            'array string' => [
                'input' => new ArrayTypeNode(new IdentifierTypeNode('string')),
                'expectedOutput' => 'string[]',
            ],
            'array generic' => [
                'input' => new GenericTypeNode(
                    new IdentifierTypeNode('array'),
                    [
                        new IdentifierTypeNode('string'),
                    ],
                ),
                'expectedOutput' => 'string[]',
            ],
            'array generic with key' => [
                'input' => new GenericTypeNode(
                    new IdentifierTypeNode('array'),
                    [
                        new IdentifierTypeNode('int'),
                        new IdentifierTypeNode('string'),
                    ],
                ),
                'expectedOutput' => 'array<int, string>',
            ],
            'callable' => [
                'input' => new IdentifierTypeNode('callable'),
                'expectedOutput' => 'callable',
            ],
            'class name fully qualified' => [
                'input' => new IdentifierTypeNode('\\App\\Class'),
                'expectedOutput' => 'App\\Class',
            ],
            'class name imported' => [
                'input' => new IdentifierTypeNode('Class'),
                'expectedOutput' => 'App\\Class',
                'resolverFactory' => fn() => $this->mock(ResolverContract::class)
                    ->expects('resolveClass')
                    ->with('Class')
                    ->andReturn('App\\Class'),
            ],
            'class name imported with generics' => [
                'input' => new GenericTypeNode(
                    new IdentifierTypeNode('\\' . Collection::class),
                    [
                        new IdentifierTypeNode('int'),
                        new IdentifierTypeNode('Class'),
                    ],
                ),
                'expectedOutput' => 'Illuminate\Support\Collection<int, App\Class>',
                'resolverFactory' => fn() => $this->mock(ResolverContract::class)
                    ->expects('resolveClass')
                    ->with('Class')
                    ->andReturn('App\\Class'),
            ],
            'mixed' => [
                'input' => new IdentifierTypeNode('mixed'),
                'expectedOutput' => 'mixed',
            ],
            'null' => [
                'input' => new IdentifierTypeNode('null'),
                'expectedOutput' => 'null',
            ],
            'object' => [
                'input' => new IdentifierTypeNode('object'),
                'expectedOutput' => 'object',
            ],
            'resource' => [
                'input' => new IdentifierTypeNode('resource'),
                'expectedOutput' => 'resource',
            ],
            'self' => [
                'input' => new IdentifierTypeNode('self'),
                'expectedOutput' => 'App\\Self',
                'resolverFactory' => fn() => $this->mock(ResolverContract::class)
                    ->expects('resolveThis')
                    ->andReturn('App\\Self'),
            ],
            'static' => [
                'input' => new IdentifierTypeNode('static'),
                'expectedOutput' => 'App\\Static',
                'resolverFactory' => fn() => $this->mock(ResolverContract::class)
                    ->expects('resolveThis')
                    ->andReturn('App\\Static'),
            ],
            'this' => [
                'input' => new ThisTypeNode(),
                'expectedOutput' => 'App\\This',
                'resolverFactory' => fn() => $this->mock(ResolverContract::class)
                    ->expects('resolveThis')
                    ->andReturn('App\\This'),
            ],
            'union' => [
                'input' => new UnionTypeNode([
                    new IdentifierTypeNode('string'),
                    new IdentifierTypeNode('int'),
                    new IdentifierTypeNode('float'),
                    new IdentifierTypeNode('bool'),
                ]),
                'expectedOutput' => 'bool|float|int|string',
            ],
            'void' => [
                'input' => new IdentifierTypeNode('void'),
                'expectedOutput' => 'void',
            ],
        ];
    }
}
