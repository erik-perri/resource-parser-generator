<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\Converters\DocBlockTypeConverter;
use ResourceParserGenerator\DataObjects\DocBlockData;
use ResourceParserGenerator\Parsers\DocBlockParser;
use ResourceParserGenerator\Resolvers\Resolver;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DocBlockData::class)]
#[CoversClass(DocBlockParser::class)]
#[CoversClass(DocBlockTypeConverter::class)]
class DocBlockParserTest extends TestCase
{
    #[DataProvider('parseProvider')]
    public function testParseProvidesExpectedResultForBasicTypes(string $docBlock, array|null $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        // Act
        $result = $parser->parse($docBlock, $resolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->properties
                ->map(fn(TypeContract $type) => $type->describe())
                ->toArray(),
        );
    }

    public static function parseProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => [],
            ],
            'no properties' => [
                'docBlock' => '
                    /**
                     * @return void
                     */
                ',
                'expectedResult' => [],
            ],
            'single property' => [
                'docBlock' => '
                    /**
                     * @property string $string
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                ],
            ],
            'multiple properties' => [
                'docBlock' => '
                    /**
                     * @property string $string
                     * @property int $number
                     * @property bool $boolean
                     * @property float $float
                     * @property array $array
                     * @property callable $callable
                     * @property null $nothing
                     * @property string|null $maybe
                     * @property string|bool|float|null $mixed
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                    'number' => 'int',
                    'boolean' => 'bool',
                    'float' => 'float',
                    'array' => 'array',
                    'callable' => 'callable',
                    'nothing' => 'null',
                    'maybe' => 'null|string',
                    'mixed' => 'bool|float|null|string',
                ],
            ],
            'generic arrays' => [
                'docBlock' => '
                    /**
                     * @property array<string> $stringArray
                     * @property array<string|int> $unionArray
                     * @property array<string, int> $stringKeyArray
                     * @property array<string|int, string|int> $unionKeyAndValueArray
                     */
                ',
                'expectedResult' => [
                    'stringArray' => 'string[]',
                    'unionArray' => 'array<int|string>',
                    'stringKeyArray' => 'array<string, int>',
                    'unionKeyAndValueArray' => 'array<int|string, int|string>',
                ],
            ],
        ];
    }

    public function testParseProvidesExpectedResultForClassTypes(): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        $resolver
            ->shouldReceive('resolveClass')
            ->with('AliasedClass')
            ->once()
            ->andReturn('AliasedClass');

        // Act
        $result = $parser->parse(
            '
                /**
                 * @property AliasedClass $aliasedClass
                 * @property \App\FullyQualifiedClass $fullyQualifiedClass
                 */
            ',
            $resolver,
        );

        // Assert
        $property = $result->properties->get('aliasedClass');
        $this->assertEquals('AliasedClass', $property->describe());

        $property = $result->properties->get('fullyQualifiedClass');
        $this->assertEquals('App\FullyQualifiedClass', $property->describe());
    }

    #[DataProvider('parseMethodProvider')]
    public function testParseProvidesExpectedResultForMethod(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        // Act
        $result = $parser->parse($docBlock, $resolver);

        // Assert
        if (!count($expectedResult)) {
            $this->assertEmpty($result->methods);
        } else {
            foreach ($expectedResult as $method => $types) {
                $this->assertEquals($types, $result->methods->get($method)->describe());
            }
        }
    }

    public static function parseMethodProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => [],
            ],
            'no methods' => [
                'docBlock' => '
                    /**
                     * @return void
                     */
                ',
                'expectedResult' => [],
            ],
            'single method' => [
                'docBlock' => '
                    /**
                     * @method void method()
                     */
                ',
                'expectedResult' => [
                    'method' => 'void',
                ],
            ],
            'multiple methods' => [
                'docBlock' => '
                    /**
                     * @method void method()
                     * @method string anotherMethod()
                     */
                ',
                'expectedResult' => [
                    'method' => 'void',
                    'anotherMethod' => 'string',
                ],
            ],
        ];
    }

    #[DataProvider('parseReturnProvider')]
    public function testParseProvidesExpectedResultForReturn(string $docBlock, string|null $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        // Act
        $result = $parser->parse($docBlock, $resolver);

        // Assert
        $this->assertEquals($expectedResult, $result->return?->describe());
    }

    public static function parseReturnProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => null,
            ],
            'no return' => [
                'docBlock' => '
                    /**
                     * @property string $string
                     */
                ',
                'expectedResult' => null,
            ],
            'single return' => [
                'docBlock' => '
                    /**
                     * @return void
                     */
                ',
                'expectedResult' => 'void',
            ],
            'multiple returns' => [
                'docBlock' => '
                    /**
                     * @return void|string
                     */
                ',
                'expectedResult' => 'string|void',
            ],
        ];
    }

    #[DataProvider('parseVarsProvider')]
    public function testParseProvidesExpectedResultForVars(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        // Act
        $result = $parser->parse($docBlock, $resolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->vars
                ->map(fn(TypeContract $type) => $type->describe())
                ->toArray(),
        );
    }

    public static function parseVarsProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => [],
            ],
            'no vars' => [
                'docBlock' => '
                    /**
                     * @return void
                     */
                ',
                'expectedResult' => [],
            ],
            'single var' => [
                'docBlock' => '
                    /**
                     * @var string $string
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                ],
            ],
            'no name' => [
                'docBlock' => '
                    /**
                     * @var string
                     */
                ',
                'expectedResult' => [
                    '' => 'string',
                ],
            ],
            'multiple vars' => [
                'docBlock' => '
                    /**
                     * @var string $string
                     * @var int $number
                     * @var bool $boolean
                     * @var float $float
                     * @var array $array
                     * @var callable $callable
                     * @var null $nothing
                     * @var string|null $maybe
                     * @var string|bool|float|null $mixed
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                    'number' => 'int',
                    'boolean' => 'bool',
                    'float' => 'float',
                    'array' => 'array',
                    'callable' => 'callable',
                    'nothing' => 'null',
                    'maybe' => 'null|string',
                    'mixed' => 'bool|float|null|string',
                ],
            ],
        ];
    }

    #[DataProvider('parseParamsProvider')]
    public function testParseProvidesExpectedResultForParams(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $resolver = $this->getResolverMock();

        // Act
        $result = $parser->parse($docBlock, $resolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->params
                ->map(fn(TypeContract $type) => $type->describe())
                ->toArray(),
        );
    }

    public static function parseParamsProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => [],
            ],
            'no vars' => [
                'docBlock' => '
                    /**
                     * @return void
                     */
                ',
                'expectedResult' => [],
            ],
            'single var' => [
                'docBlock' => '
                    /**
                     * @param string $string
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                ],
            ],
            'multiple vars' => [
                'docBlock' => '
                    /**
                     * @param string $string
                     * @param int $number
                     * @param string|null $maybe
                     * @param string|bool|float|null $mixed
                     */
                ',
                'expectedResult' => [
                    'string' => 'string',
                    'number' => 'int',
                    'maybe' => 'null|string',
                    'mixed' => 'bool|float|null|string',
                ],
            ],
        ];
    }

    private function getResolverMock(): Resolver|MockInterface
    {
        /**
         * @var Resolver $mockResolver
         */
        $mockResolver = $this->mock(Resolver::class);

        return $mockResolver;
    }
}
