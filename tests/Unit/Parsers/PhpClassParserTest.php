<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use Exception;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;
use ResourceParserGenerator\Parsers\PhpClassParser;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassScope::class)]
#[CoversClass(PhpClassParser::class)]
class PhpClassParserTest extends TestCase
{
    #[DataProvider('classCodeProvider')]
    public function testParsesClassProperties(string $code, array $expectations): void
    {
        // Arrange
        $parser = $this->make(PhpClassParser::class);

        $classAst = $this->getClass($code, 'TestClass');

        // Act
        $class = $parser->parse($classAst, $this->getFileScopeMock());

        // Assert
        $this->assertCount(
            count($expectations),
            $class->properties(),
            'Failed asserting that class {$class->name} has correct number of properties.',
        );

        foreach ($expectations as $propertyExpectations) {
            $property = $class->property($propertyExpectations['name']);

            $this->assertEquals(
                $propertyExpectations['isPrivate'],
                $property->isPrivate(),
                "Failed asserting that property {$property->name} is private.",
            );
            $this->assertEquals(
                $propertyExpectations['isProtected'],
                $property->isProtected(),
                "Failed asserting that property {$property->name} is protected.",
            );
            $this->assertEquals(
                $propertyExpectations['isPublic'],
                $property->isPublic(),
                "Failed asserting that property {$property->name} is public.",
            );
            $this->assertEquals(
                $propertyExpectations['isReadonly'],
                $property->isReadonly(),
                "Failed asserting that property {$property->name} is readonly.",
            );
            $this->assertEquals(
                $propertyExpectations['isStatic'],
                $property->isStatic(),
                "Failed asserting that property {$property->name} is static.",
            );
            $this->assertEquals(
                $propertyExpectations['name'],
                $property->name,
                "Failed asserting that property {$property->name} is named {$propertyExpectations['name']}.",
            );
            $this->assertEquals(
                $propertyExpectations['type'],
                $property->type->name(),
                "Failed asserting that property {$property->name} is of type {$propertyExpectations['type']}.",
            );
        }
    }

    public static function classCodeProvider(): array
    {
        return [
            'private' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    private string \$propertyOne;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => true,
                        'isProtected' => false,
                        'isPublic' => false,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertyOne',
                        'type' => 'string',
                    ],
                ],
            ],
            'protected' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    protected int \$propertyTwo;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => false,
                        'isProtected' => true,
                        'isPublic' => false,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertyTwo',
                        'type' => 'int',
                    ],
                ],
            ],
            'public' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    public float \$propertyThree;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => false,
                        'isProtected' => false,
                        'isPublic' => true,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertyThree',
                        'type' => 'float',
                    ],
                ],
            ],
            'public due to no visibility spec' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    static float \$propertyFour;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => false,
                        'isProtected' => false,
                        'isPublic' => true,
                        'isReadonly' => false,
                        'isStatic' => true,
                        'name' => 'propertyFour',
                        'type' => 'float',
                    ],
                ],
            ],
            'static' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    public static mixed \$propertyFive;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => false,
                        'isProtected' => false,
                        'isPublic' => true,
                        'isReadonly' => false,
                        'isStatic' => true,
                        'name' => 'propertyFive',
                        'type' => 'mixed',
                    ],
                ],
            ],
            'readonly' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    private readonly string \$propertySix;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => true,
                        'isProtected' => false,
                        'isPublic' => false,
                        'isReadonly' => true,
                        'isStatic' => false,
                        'name' => 'propertySix',
                        'type' => 'string',
                    ],
                ],
            ],
            'multiple' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    private array \$propertySeven, \$propertyEight;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => true,
                        'isProtected' => false,
                        'isPublic' => false,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertySeven',
                        'type' => 'array',
                    ],
                    [
                        'isPrivate' => true,
                        'isProtected' => false,
                        'isPublic' => false,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertyEight',
                        'type' => 'array',
                    ],
                ],
            ],
        ];
    }

    private function getClass(string $contents, string $name): Class_
    {
        $parser = $this->make(Parser::class);
        $ast = $parser->parse($contents);

        $nodeFinder = new NodeFinder();
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);
        foreach ($classes as $class) {
            /**
             * @var Class_ $class
             */
            if ($class->name->name === $name) {
                return $class;
            }
        }

        throw new Exception(sprintf('Class "%s" not found', $name));
    }

    private function getFileScopeMock(): FileScope
    {
        /**
         * @var FileScope $mockFileScope
         */
        $mockFileScope = $this->mock(FileScope::class)
            ->shouldReceive('addClass')
            ->with(\Mockery::type(ClassScope::class))
            ->once()
            ->andReturnSelf()
            ->getMock();

        return $mockFileScope;
    }
}
