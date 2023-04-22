<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Tests\Examples\Models\User;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassFileTypehintParser::class)]
class ClassFileTypehintParserTest extends TestCase
{
    public function testGetsFullClassFromImportedClass(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Examples/UserResource.php';

        // Act
        $typehints = $this->performAction(UserResource::class, $classFile);

        // Assert
        $this->assertEquals(
            [],
            $typehints->methods,
        );
        $this->assertEquals(
            [
                'resource' => [User::class],
                'untyped' => ['mixed'],
            ],
            $typehints->properties,
        );
    }

    public function testGetsCompoundTypesFromRegularAndReadOnlyProperties(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Examples/Models/User.php';

        // Act
        $typehints = $this->performAction(User::class, $classFile);

        // Assert
        $this->assertEquals(
            [
                'getRouteKey' => ['string'],
            ],
            $typehints->methods,
        );
        $this->assertEquals(
            [
                'id' => ['int'],
                'ulid' => ['string'],
                'email' => ['string'],
                'name' => ['string'],
                'created_at' => [CarbonImmutable::class, 'null'],
                'updated_at' => [CarbonImmutable::class, 'null'],
            ],
            $typehints->properties,
        );
    }

    /**
     * @param class-string $className
     * @param string $classFile
     * @return ClassTypehints
     * @throws ReflectionException
     */
    private function performAction(string $className, string $classFile): ClassTypehints
    {
        /** @var ClassFileTypehintParser $parser */
        $parser = app(ClassFileTypehintParser::class);

        return $parser->parse($className, $classFile);
    }
}
