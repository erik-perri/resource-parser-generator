<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use Carbon\CarbonImmutable;
use ReflectionException;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;

/**
 * @covers ClassFileTypehintParser
 */
class ClassFileTypehintParserTest extends TestCase
{
    public function testGetsFullClassFromImportedClass(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Stubs/UserResource.php';

        // Act
        $typehints = $this->performAction(UserResource::class, $classFile);

        // Assert
        $this->assertEquals([
            'resource' => User::class,
        ], $typehints);
    }

    public function testGetsCompoundTypesFromRegularAndReadOnlyProperties(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Stubs/Models/User.php';

        // Act
        $typehints = $this->performAction(User::class, $classFile);

        // Assert
        $this->assertEquals([
            'getRouteKey()' => 'string',
            'id' => 'int',
            'ulid' => 'string',
            'email' => 'string',
            'name' => 'string',
            'created_at' => [CarbonImmutable::class, 'null'],
            'updated_at' => [CarbonImmutable::class, 'null'],
        ], $typehints);
    }

    /**
     * @param class-string $className
     * @param string $classFile
     * @return array<string, string|string[]>
     * @throws ReflectionException
     */
    private function performAction(string $className, string $classFile): array
    {
        /** @var ClassFileTypehintParser $parser */
        $parser = app(ClassFileTypehintParser::class);

        return $parser->parse($className, $classFile);
    }
}
