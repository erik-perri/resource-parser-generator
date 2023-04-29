<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\TypeContract;
use ResourceParserGenerator\Parsers\DataObjects\DocBlock;
use ResourceParserGenerator\Parsers\DocBlockParser;
use ResourceParserGenerator\Parsers\DocBlockTypeParser;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(DocBlock::class)]
#[CoversClass(DocBlockParser::class)]
#[CoversClass(DocBlockTypeParser::class)]
class DocBlockParserTest extends TestCase
{
    #[DataProvider('parseProvider')]
    public function testParseProvidesExpectedResultForBasicTypes(string $docBlock, array|null $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        // Act
        $result = $parser->parse($docBlock, $classResolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->properties()
                ->map(fn(TypeContract $type) => $type->name())
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
                     * @property-read resource $resource
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
                    'resource' => 'resource',
                    'nothing' => 'null',
                    'maybe' => 'string|null',
                    'mixed' => 'string|bool|float|null',
                ],
            ],
        ];
    }

    public function testParseProvidesExpectedResultForClassTypes(): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        $classResolver
            ->shouldReceive('resolve')
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
            $classResolver
        );

        // Assert
        $property = $result->property('aliasedClass');
        $this->assertEquals('AliasedClass', $property->name());

        $property = $result->property('fullyQualifiedClass');
        $this->assertEquals('App\FullyQualifiedClass', $property->name());
    }

    #[DataProvider('parseMethodProvider')]
    public function testParseProvidesExpectedResultForMethod(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        // Act
        $result = $parser->parse($docBlock, $classResolver);

        // Assert
        if (!count($expectedResult)) {
            $this->assertEmpty($result->methods());
        } else {
            foreach ($expectedResult as $method => $types) {
                $this->assertEquals($types, $result->method($method)->name());
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
    public function testParseProvidesExpectedResultForReturn(string $docBlock, string $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        // Act
        $result = $parser->parse($docBlock, $classResolver);

        // Assert
        $this->assertEquals($expectedResult, $result->return()->name());
    }

    public static function parseReturnProvider(): array
    {
        return [
            'empty' => [
                'docBlock' => '',
                'expectedResult' => 'untyped',
            ],
            'no return' => [
                'docBlock' => '
                    /**
                     * @property string $string
                     */
                ',
                'expectedResult' => 'untyped',
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
                'expectedResult' => 'void|string',
            ],
        ];
    }

    #[DataProvider('parseVarsProvider')]
    public function testParseProvidesExpectedResultForVars(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        // Act
        $result = $parser->parse($docBlock, $classResolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->vars()
                ->map(fn(TypeContract $type) => $type->name())
                ->toArray()
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
                     * @var resource $resource
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
                    'resource' => 'resource',
                    'nothing' => 'null',
                    'maybe' => 'string|null',
                    'mixed' => 'string|bool|float|null',
                ],
            ],
        ];
    }

    #[DataProvider('parseParamsProvider')]
    public function testParseProvidesExpectedResultForParams(string $docBlock, array $expectedResult): void
    {
        // Arrange
        $parser = $this->make(DocBlockParser::class);

        $classResolver = $this->getClassNameResolverMock();

        // Act
        $result = $parser->parse($docBlock, $classResolver);

        // Assert
        $this->assertEquals(
            $expectedResult,
            $result->params()
                ->map(fn(TypeContract $type) => $type->name())
                ->toArray()
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
                    'maybe' => 'string|null',
                    'mixed' => 'string|bool|float|null',
                ],
            ],
        ];
    }

    private function getClassNameResolverMock(): ClassNameResolver|MockInterface
    {
        /**
         * @var ClassNameResolver $mockClassResolver
         */
        $mockClassResolver = $this->mock(ClassNameResolver::class);

        return $mockClassResolver;
    }
}
