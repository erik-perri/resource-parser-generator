<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\PhpParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ResourceParserGenerator\DataObjects\ClassTypehints;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\DocBlock\ClassFileTypehintParser;
use ResourceParserGenerator\Parsers\DocBlock\DocBlockTagTypeConverter;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnArrayTypeExtractor;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnArrayTypeLocator;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnArrayTypeParser;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnParser;
use ResourceParserGenerator\Parsers\PhpParser\ExpressionObjectTypeParser;
use ResourceParserGenerator\Parsers\PhpParser\UseStatementParser;
use ResourceParserGenerator\Parsers\ResolveScope;
use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Visitors\FindArrayReturnVisitor;
use ResourceParserGenerator\Visitors\FindClassMethodWithNameVisitor;

#[CoversClass(ClassMethodReturnArrayTypeParser::class)]
#[UsesClass(ClassFileFinder::class)]
#[UsesClass(ClassFileTypehintParser::class)]
#[UsesClass(ClassMethodReturnArrayTypeExtractor::class)]
#[UsesClass(ClassMethodReturnArrayTypeLocator::class)]
#[UsesClass(ClassMethodReturnParser::class)]
#[UsesClass(ClassTypehints::class)]
#[UsesClass(DocBlockTagTypeConverter::class)]
#[UsesClass(ExpressionObjectTypeParser::class)]
#[UsesClass(FindArrayReturnVisitor::class)]
#[UsesClass(FindClassMethodWithNameVisitor::class)]
#[UsesClass(ResolveScope::class)]
#[UsesClass(ResourceParserGeneratorServiceProvider::class)]
#[UsesClass(UseStatementParser::class)]
class ClassMethodReturnArrayTypeParserTest extends TestCase
{
    #[DataProvider('userResourceProvider')]
    public function testGetsExpectedValues(
        string $classFile,
        string $className,
        string $methodName,
        array $expectedReturns,
    ): void {
        // Arrange
        /** @var ClassMethodReturnArrayTypeParser $parser */
        $parser = $this->app->make(ClassMethodReturnArrayTypeParser::class);

        // Act
        $returns = $parser->parse($className, $classFile, $methodName);

        // Assert
        $this->assertEquals($expectedReturns, $returns);
    }

    public static function userResourceProvider(): array
    {
        return [
            'authentication' => [
                'classFile' => dirname(__DIR__, 3) . '/Stubs/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'authentication',
                'expectedReturns' => [
                    'id' => ['string'],
                    'email' => ['string'],
                    'name' => ['string'],
                ],
            ],
            'adminList' => [
                'classFile' => dirname(__DIR__, 3) . '/Stubs/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'adminList',
                'expectedReturns' => [
                    'id' => ['string'],
                    'email' => ['string'],
                    'name' => ['string'],
                    'created_at' => ['null', 'string'],
                    'updated_at' => ['null', 'string'],
                ],
            ],
            'combined' => [
                'classFile' => dirname(__DIR__, 3) . '/Stubs/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'combined',
                'expectedReturns' => [
                    'email' => ['null', 'string'],
                    'name' => ['string', 'undefined'],
                ],
            ],
        ];
    }
}
