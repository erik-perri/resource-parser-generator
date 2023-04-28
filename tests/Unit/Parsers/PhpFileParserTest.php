<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\PhpFileParser;
use ResourceParserGenerator\Parsers\Scopes\ClassScope;
use ResourceParserGenerator\Parsers\Scopes\FileScope;
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
        $this->assertSame($expected, $result->getNamespace());
    }

    public static function namespaceCodeProvider(): array
    {
        return [
            'namespace' => [
                'contents' => <<<PHP
<?php

namespace ResourceParserGenerator\Tests\Examples;

class Test {
}
PHP,
                'expected' => 'ResourceParserGenerator\Tests\Examples'
            ],
            'no namespace' => [
                'contents' => <<<PHP

class Test {
}
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
                'exceptionMessage' => 'Multiple namespaces found',
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
        $this->assertSame($expected, $result->getUses());
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

    public function testParsesClasses(): void
    {
        // Arrange
        $parser = $this->make(PhpFileParser::class);
        $contents = <<<PHP
<?php

namespace ResourceParserGenerator\Tests\Examples;

class TestClass
{
    public function method(string \$parameter): void
    {
        //
    }
}
PHP;

        // Act
        $result = $parser->parse($contents);

        // Assert
        $class = $result->getClass('TestClass');
        $this->assertEquals('ResourceParserGenerator\Tests\Examples', $class->file->getNamespace());
        $this->assertEquals('TestClass', $class->name);
    }
}
