<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use Exception;
use Mockery\MockInterface;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Contracts\ResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\ClassMethod;
use ResourceParserGenerator\Parsers\DataObjects\ClassProperty;
use ResourceParserGenerator\Parsers\DataObjects\ClassScope;
use ResourceParserGenerator\Parsers\PhpClassParser;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassMethod::class)]
#[CoversClass(ClassProperty::class)]
#[CoversClass(ClassScope::class)]
#[CoversClass(PhpClassParser::class)]
class PhpClassParserTest extends TestCase
{
    #[DataProvider('classCodeProvider')]
    public function testParsesClassProperties(string $code, array $expectations): void
    {
        // Arrange
        $classAst = $this->getClass($code, 'TestClass');

        /**
         * @var ResolverContract|MockInterface $mockResolver
         */
        $mockResolver = $this->mock(ResolverContract::class);
        $classScope = new ClassScope('AnotherTestClass');

        // Act
        $this->make(PhpClassParser::class)->parse($classAst, $classScope, $mockResolver);

        // Assert
        $this->assertCount(
            count($expectations),
            $classScope->properties(),
            'Failed asserting that class {$class->name} has correct number of properties.',
        );

        foreach ($expectations as $propertyExpectations) {
            $property = $classScope->property($propertyExpectations['name']);

            $this->assertEquals(
                $propertyExpectations['isPrivate'],
                $property->isPrivate(),
                "Failed asserting that property $property->name is private.",
            );
            $this->assertEquals(
                $propertyExpectations['isProtected'],
                $property->isProtected(),
                "Failed asserting that property $property->name is protected.",
            );
            $this->assertEquals(
                $propertyExpectations['isPublic'],
                $property->isPublic(),
                "Failed asserting that property $property->name is public.",
            );
            $this->assertEquals(
                $propertyExpectations['isReadonly'],
                $property->isReadonly(),
                "Failed asserting that property $property->name is readonly.",
            );
            $this->assertEquals(
                $propertyExpectations['isStatic'],
                $property->isStatic(),
                "Failed asserting that property $property->name is static.",
            );
            $this->assertEquals(
                $propertyExpectations['name'],
                $property->name,
                "Failed asserting that property $property->name is named {$propertyExpectations['name']}.",
            );
            $this->assertEquals(
                $propertyExpectations['type'],
                $property->type->name(),
                "Failed asserting that property $property->name is of type {$propertyExpectations['type']}.",
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
            'compound' => [
                'code' => <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class TestClass
{
    private string|int \$propertyNine;
}
PHP,
                'expectations' => [
                    [
                        'isPrivate' => true,
                        'isProtected' => false,
                        'isPublic' => false,
                        'isReadonly' => false,
                        'isStatic' => false,
                        'name' => 'propertyNine',
                        'type' => 'string|int',
                    ],
                ],
            ],
        ];
    }

    public function testParsesClassMethods(): void
    {
        // Arrange
        $code = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
class AnotherTestClass
{
    private function method(string \$argumentOne, int \$argumentTwo): float
    {
        return 1.0;
    }
}
PHP;

        $classAst = $this->getClass($code, 'AnotherTestClass');

        /**
         * @var ResolverContract|MockInterface $mockResolver
         */
        $mockResolver = $this->mock(ResolverContract::class);
        $classScope = new ClassScope('AnotherTestClass');

        // Act
        $this->make(PhpClassParser::class)->parse($classAst, $classScope, $mockResolver);
        $method = $classScope->method('method');

        // Assert
        $this->assertTrue($method->isPrivate());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertEquals('method', $method->name);
        $this->assertEquals('float', $method->returnType->name());
        $this->assertCount(2, $method->parameters());
        $this->assertEquals('string', $method->parameters()->get('argumentOne')->name());
        $this->assertEquals('int', $method->parameters()->get('argumentTwo')->name());
    }

    public function testParsesDocBlocks(): void
    {
        // Arrange
        $code = <<<PHP
<?php
namespace ResourceParserGenerator\Tests\Examples;
/**
 * @property string \$hintedProperty
 * @method float hintedMethod(string \$argumentOne, int \$argumentTwo)
 */
class TestClass
{
    /**
     * @var string|null
     */
    private \$explicitProperty;
    
    /**
     * @param string|null \$argumentOne
     * @param int \$argumentTwo
     * @return float
     */
    private function method(mixed \$argumentOne, int \$argumentTwo): float
    {
        return 1.0;
    }
}
PHP;

        $classAst = $this->getClass($code, 'TestClass');

        /**
         * @var ResolverContract|MockInterface $mockResolver
         */
        $mockResolver = $this->mock(ResolverContract::class);
        $classScope = new ClassScope('TestClass');

        // Act
        $this->make(PhpClassParser::class)->parse($classAst, $classScope, $mockResolver);
        $method = $classScope->method('method');
        $property = $classScope->property('explicitProperty');

        // Assert
        $this->assertTrue($classScope->docBlock->hasProperty('hintedProperty'));
        $this->assertTrue($classScope->docBlock->hasMethod('hintedMethod'));
        $this->assertEquals('string', $classScope->docBlock->property('hintedProperty')->name());
        $this->assertEquals('float', $classScope->docBlock->method('hintedMethod')->name());

        $this->assertTrue($property->docBlock->hasVar(''));
        $this->assertEquals('string|null', $property->docBlock->var('')->name());

        $this->assertTrue($method->docBlock->hasParam('argumentOne'));
        $this->assertTrue($method->docBlock->hasParam('argumentTwo'));
        $this->assertEquals('string|null', $method->docBlock->param('argumentOne')->name());
        $this->assertEquals('int', $method->docBlock->param('argumentTwo')->name());
        $this->assertEquals('float', $method->docBlock->return()->name());
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
}
