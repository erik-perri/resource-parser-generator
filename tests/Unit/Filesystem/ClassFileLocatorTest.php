<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Filesystem;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Filesystem\ClassFileLocator;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;
use RuntimeException;

#[CoversClass(ClassFileLocator::class)]
class ClassFileLocatorTest extends TestCase
{
    #[DataProvider('invalidPathProvider')]
    public function testFailsWhenPassedInvalidPaths(string $path, string $exception): void
    {
        // Expectations
        $this->expectException($exception);

        // Act
        new ClassFileLocator($path);

        // Assert
        // No assertions, only expectations.
    }

    public static function invalidPathProvider(): array
    {
        return [
            'empty' => [
                'path' => '',
                'exception' => InvalidArgumentException::class,
            ],
            'non-existent' => [
                'path' => 'non-existent/path',
                'exception' => InvalidArgumentException::class,
            ],
            'non an autoloader' => [
                'path' => sprintf('%s/Examples/FakeVendor', dirname(__DIR__, 2)),
                'exception' => InvalidArgumentException::class,
            ],
        ];
    }

    #[DataProvider('existingClassProvider')]
    public function testGetReturnsExpectedResultsForExistingClasses(string $class, string $file): void
    {
        // Arrange
        $locator = new ClassFileLocator(dirname(__DIR__, 3) . '/vendor');

        // Act
        $result = $locator->get($class);

        // Assert
        $this->assertSame($file, realpath($result));
    }

    public static function existingClassProvider(): array
    {
        return [
            UserResource::class => [
                'class' => UserResource::class,
                'file' => sprintf('%s/Examples/UserResource.php', dirname(__DIR__, 2)),
            ],
        ];
    }

    public function testGetThrowsExceptionForMissingClass(): void
    {
        // Expectations
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find file for class "App\UnknownClass"');

        // Arrange
        $locator = new ClassFileLocator(dirname(__DIR__, 3) . '/vendor');

        // Act
        $locator->get('App\UnknownClass');

        // Assert
        // No assertions, only expectations.
    }

    #[DataProvider('existsResultProvider')]
    public function testExistsReturnsExpectedResult(string $class, bool $expected): void
    {
        // Arrange
        $locator = new ClassFileLocator(dirname(__DIR__, 3) . '/vendor');

        // Act
        $result = $locator->exists($class);

        // Assert
        $this->assertSame($expected, $result);
    }

    public static function existsResultProvider(): array
    {
        return [
            'existing' => [
                'class' => UserResource::class,
                'expected' => true,
            ],
            'non-existing' => [
                'class' => 'App\UnknownClass',
                'expected' => false,
            ],
        ];
    }
}
