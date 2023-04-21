<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\DocBlock;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionException;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\UseStatementParser;
use ResourceParserGenerator\Parsers\ResolveScope;
use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassFileTypehintParser::class)]
#[UsesClass(ClassTypehints::class)]
#[UsesClass(DocBlockTagTypeConverter::class)]
#[UsesClass(ResolveScope::class)]
#[UsesClass(ResourceParserGeneratorServiceProvider::class)]
#[UsesClass(UseStatementParser::class)]
class ClassFileTypehintParserTest extends TestCase
{
    public function testGetsFullClassFromImportedClass(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Stubs/UserResource.php';

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
            ],
            $typehints->properties,
        );
    }

    public function testGetsCompoundTypesFromRegularAndReadOnlyProperties(): void
    {
        // Arrange
        $classFile = dirname(__DIR__, 3) . '/Stubs/Models/User.php';

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
