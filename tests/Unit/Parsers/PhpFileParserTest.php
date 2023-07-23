<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Parsers\Data\ClassProperty;
use ResourceParserGenerator\Parsers\Data\ClassScope;
use ResourceParserGenerator\Parsers\Data\EnumScope;
use ResourceParserGenerator\Parsers\Data\FileScope;
use ResourceParserGenerator\Parsers\PhpFileParser;
use ResourceParserGenerator\Tests\TestCase;
use RuntimeException;

#[CoversClass(FileScope::class)]
#[CoversClass(ClassScope::class)]
#[CoversClass(ClassProperty::class)]
#[CoversClass(PhpFileParser::class)]
class PhpFileParserTest extends TestCase
{
    /** @dataProvider namespaceCodeProvider */
    #[DataProvider('namespaceCodeProvider')]
    public function testParsesNamespace(string $contents, string|null $expected): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);

        // Act
        $result = $parser->parse($contents);

        // Assert
        $this->assertSame($expected, $result->namespace());
    }

    public static function namespaceCodeProvider(): array
    {
        return [
            'namespace' => [
                'contents' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class Test {}
PHP,
                'expected' => 'ResourceParserGenerator\Tests\Examples',
            ],
            'no namespace' => [
                'contents' => <<<PHP
<?php
class Test {}
PHP,
                'expected' => null,
            ],
        ];
    }

    /** @dataProvider invalidContentProvider */
    #[DataProvider('invalidContentProvider')]
    public function testThrowsErrorOnInvalidContent(
        string $contents,
        string $exception,
        string $exceptionMessage,
    ): void {
        // Expectations
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        // Arrange
        $parser = $this->make(PhpFileParser::class);

        // Act
        $parser->parse($contents);

        // Assert
        // No assertions, only expectations.
    }

    public static function invalidContentProvider(): array
    {
        return [
            'multiple namespaces' => [
                'contents' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
namespace ResourceParserGenerator\Tests\ExamplesTwo;
PHP,
                'exception' => RuntimeException::class,
                'exceptionMessage' => 'Multiple namespaces not supported',
            ],
            'duplicate aliases' => [
                'contents' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
use ResourceParserGenerator\Tests\ExampleOne as ExampleOne;
use ResourceParserGenerator\Tests\ExampleTwo as ExampleOne;
PHP,
                'exception' => RuntimeException::class,
                'exceptionMessage' => 'Alias "ExampleOne" already exists',
            ],
            'empty' => [
                'contents' => '',
                'exception' => RuntimeException::class,
                'exceptionMessage' => 'Could not parse file',
            ],
        ];
    }

    /** @dataProvider useCodeProvider */
    #[DataProvider('useCodeProvider')]
    public function testParsesUseStatements(string $contents, array $expected): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);

        // Act
        $result = $parser->parse($contents);

        // Assert
        $this->assertSame($expected, $result->imports()->toArray());
    }

    public static function useCodeProvider(): array
    {
        return [
            'single use' => [
                'contents' => <<<PHP
<?php
use ResourceParserGenerator\Tests\Examples\Resources\UserResource;
PHP,
                'expected' => [
                    'UserResource' => 'ResourceParserGenerator\Tests\Examples\Resources\UserResource',
                ],
            ],
            'multiple uses' => [
                'contents' => <<<PHP
<?php
use ResourceParserGenerator\Tests\Examples\{NoHints,Resources\UserResource};
PHP,
                'expected' => [
                    'NoHints' => 'ResourceParserGenerator\Tests\Examples\NoHints',
                    'UserResource' => 'ResourceParserGenerator\Tests\Examples\Resources\UserResource',
                ],
            ],
            'multiple uses with aliases and whitespace and comments' => [
                'contents' => <<<PHP
<?php
use ResourceParserGenerator\Tests\Examples\{
    NoHints as NoHintsAlias, // No hints
    Resources\UserResource as UserResourceAlias // User resource
};
PHP,
                'expected' => [
                    'NoHintsAlias' => 'ResourceParserGenerator\Tests\Examples\NoHints',
                    'UserResourceAlias' => 'ResourceParserGenerator\Tests\Examples\Resources\UserResource',
                ],
            ],
        ];
    }

    public function testParsesMultipleClasses(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClassOne {}
class TestClassTwo {}
PHP;

        // Act
        $result = $parser->parse($contents);

        // Assert
        $this->assertCount(2, $result->classes());
    }

    public function testParsesAnonymousClasses(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
\$class = new class {};
PHP;

        // Act
        $result = $parser->parse($contents);

        // Assert
        $class = $result->class('AnonymousClass3');
        $this->assertEquals('AnonymousClass3', $class->name());
    }

    public function testHelpsResolveClassNames(): void
    {
        // Arrange
        $locatorContract = $this->mock(ClassFileLocatorContract::class);

        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
use ResourceParserGenerator\Imports\ImportedClass;
use ResourceParserGenerator\Imports\RelativePath;
class TestClass
{
    private AdjacentClass \$propertyOne;
    private ImportedClass \$propertyTwo;
    private RelativePath\RelativeClass \$propertyThree;
    private \ResourceParserGenerator\Tests\Examples\AbsoluteClass \$propertyFour;
}
PHP;

        $locatorContract->expects('exists')
            ->with('ResourceParserGenerator\Tests\Examples\AdjacentClass')
            ->andReturn(true);
        $locatorContract->expects('exists')
            ->with('ResourceParserGenerator\Imports\RelativePath\RelativeClass')
            ->andReturn(true);

        // Act
        $result = $parser->parse($contents);

        // Assert
        $class = $result->class('TestClass');
        $this->assertEquals(
            'ResourceParserGenerator\Tests\Examples\AdjacentClass',
            $class->property('propertyOne')->type()->describe(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Imports\ImportedClass',
            $class->property('propertyTwo')->type()->describe(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Imports\RelativePath\RelativeClass',
            $class->property('propertyThree')->type()->describe(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Tests\Examples\AbsoluteClass',
            $class->property('propertyFour')->type()->describe(),
        );
    }

    public function testResolvesNestedClassStructures(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);

        // Act
        $result = $parser->parse(
            file_get_contents(dirname(__DIR__, 2) . '/Examples/Nesting/ClassC.php'),
        );
        $class = $result->class('ClassC');

        // Assert
        $this->assertEquals('mixed', $class->property('variableZ')->type()->describe());
        $this->assertEquals('int', $class->property('variableA')->type()->describe());
        $this->assertEquals('string', $class->property('variableB')->type()->describe());
        $this->assertEquals('bool', $class->property('variableC')->type()->describe());
    }

    public function testParsesEnums(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
enum TestEnum
{
    case A;
    case B;
    case C;
}
PHP;

        // Act
        $result = $parser->parse($contents);

        // Assert
        $enum = $result->enum('TestEnum');
        $this->assertInstanceOf(EnumScope::class, $enum);
        $this->assertEquals('TestEnum', $enum->name());
        $this->assertEquals('untyped', $enum->propertyType('value')->describe());
    }

    public function testParsesBackedEnums(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
enum IntBackedEnum: int
{
    case A = 1;
    case B = 2;
    case C = 3;
}
PHP;

        // Act
        $result = $parser->parse($contents);

        // Assert
        $enum = $result->enum('IntBackedEnum');
        $this->assertInstanceOf(EnumScope::class, $enum);
        $this->assertEquals('IntBackedEnum', $enum->name());
        $this->assertEquals('int', $enum->propertyType('value')->describe());
    }
}
