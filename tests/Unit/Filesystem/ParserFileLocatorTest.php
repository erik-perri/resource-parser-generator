<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Filesystem\ResourceFileLocator;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ResourceFileLocator::class)]
class ParserFileLocatorTest extends TestCase
{
    public function testFilesReturnsExpectedResults(): void
    {
        // Arrange
        $locator = new ResourceFileLocator();

        // Act
        $result = $locator->files(new ResourcePath(dirname(__DIR__, 2) . '/Examples/Resources'));

        // Assert
        $this->assertEquals([
            dirname(__DIR__, 2) . '/Examples/Resources/Nested/RelatedResource.php',
            dirname(__DIR__, 2) . '/Examples/Resources/PostResource.php',
            dirname(__DIR__, 2) . '/Examples/Resources/UserResource.php',
        ], $result);
    }

    public function testFilesCanBeFiltered(): void
    {
        // Arrange
        $locator = new ResourceFileLocator();

        // Act
        $result = $locator->files(new ResourcePath(
            dirname(__DIR__, 2) . '/Examples/Resources',
            fileMatch: '/User.*\.php$/i',
        ));

        // Assert
        $this->assertEquals([
            dirname(__DIR__, 2) . '/Examples/Resources/UserResource.php',
        ], $result);
    }
}
