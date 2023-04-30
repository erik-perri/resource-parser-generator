<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;
use ResourceParserGenerator\Parsers\PhpFileParser;
use ResourceParserGenerator\Tests\TestCase;
use RuntimeException;

#[CoversClass(ClassScope::class)]
#[CoversClass(FileScope::class)]
#[CoversClass(PhpFileParser::class)]
class PhpFileParserTest extends TestCase
{
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
                'expected' => 'ResourceParserGenerator\Tests\Examples'
            ],
            'no namespace' => [
                'contents' => <<<PHP
<?php
class Test {}
PHP,
                'expected' => null
            ],
        ];
    }

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
use ResourceParserGenerator\Tests\Examples\UserResource;
PHP,
                'expected' => [
                    'UserResource' => 'ResourceParserGenerator\Tests\Examples\UserResource',
                ],
            ],
            'multiple uses' => [
                'contents' => <<<PHP
<?php
use ResourceParserGenerator\Tests\Examples\{NoHints,UserResource};
PHP,
                'expected' => [
                    'NoHints' => 'ResourceParserGenerator\Tests\Examples\NoHints',
                    'UserResource' => 'ResourceParserGenerator\Tests\Examples\UserResource',
                ],
            ],
            'multiple uses with aliases and whitespace and comments' => [
                'contents' => <<<PHP
<?php
use ResourceParserGenerator\Tests\Examples\{
    NoHints as NoHintsAlias, // No hints
    UserResource as UserResourceAlias // User resource
};
PHP,
                'expected' => [
                    'NoHintsAlias' => 'ResourceParserGenerator\Tests\Examples\NoHints',
                    'UserResourceAlias' => 'ResourceParserGenerator\Tests\Examples\UserResource',
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
        $this->assertEquals('AnonymousClass3', $class->name);
    }

    public function testHelpsResolveClassNames(): void
    {
        // Arrange
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

        // Act
        $result = $parser->parse($contents);

        // Assert
        $class = $result->class('TestClass');
        $this->assertEquals(
            'ResourceParserGenerator\Tests\Examples\AdjacentClass',
            $class->property('propertyOne')->type->name(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Imports\ImportedClass',
            $class->property('propertyTwo')->type->name(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Imports\RelativePath\RelativeClass',
            $class->property('propertyThree')->type->name(),
        );
        $this->assertEquals(
            'ResourceParserGenerator\Tests\Examples\AbsoluteClass',
            $class->property('propertyFour')->type->name(),
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
        $this->assertEquals('mixed', $class->property('variableZ')->type->name());
        $this->assertEquals('int', $class->property('variableA')->type->name());
        $this->assertEquals('string', $class->property('variableB')->type->name());
        $this->assertEquals('bool', $class->property('variableC')->type->name());
    }
}
