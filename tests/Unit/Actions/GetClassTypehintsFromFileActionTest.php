<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Actions;

use Carbon\CarbonImmutable;
use ReflectionException;
use ResourceParserGenerator\Actions\GetClassTypehintsFromFileAction;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;

/**
 * @covers GetClassTypehintsFromFileAction
 */
class GetClassTypehintsFromFileActionTest extends TestCase
{
    public function testGetsFullClassFromImportedClass(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 2) . '/Stubs/UserResource.php';

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
        $classFile = dirname(__DIR__, 2) . '/Stubs/Models/User.php';

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
        /** @var GetClassTypehintsFromFileAction $action */
        $action = app(GetClassTypehintsFromFileAction::class);

        return $action->execute($className, $classFile);
    }
}
