<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\PhpParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\ClassMethodReturnTypeParser;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassMethodReturnTypeParser::class)]
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
        /**
         * @var ClassMethodReturnTypeParser $parser
         */
        $parser = $this->app->make(ClassMethodReturnTypeParser::class);

        // Act
        $returns = $parser->parse($className, $classFile, $methodName);

        // Assert
        $this->assertEquals($expectedReturns, $returns);
    }

    public static function userResourceProvider(): array
    {
        return [
            'authentication' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'authentication',
                'expectedReturns' => [
                    'id' => ['string'],
                    'email' => ['string'],
                    'name' => ['string'],
                ],
            ],
            'adminList' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
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
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'combined',
                'expectedReturns' => [
                    'email' => ['null', 'string'],
                    'name' => ['string', 'undefined'],
                ],
            ],
            'ternaries' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'ternaries',
                'expectedReturns' => [
                    'ternary_to_int' => ['int'],
                    'ternary_to_compound' => ['bool', 'int', 'string'],
                ],
            ],
            'scalars' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'scalars',
                'expectedReturns' => [
                    'string' => ['string'],
                    'negative_number' => ['int'],
                    'positive_number' => ['int'],
                    'neutral_number' => ['int'],
                    'float' => ['float'],
                    'boolean_true' => ['bool'],
                    'boolean_false' => ['bool'],
                    'null' => ['null'],
                ],
            ],
            'parameter with dependency from extends' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'usingParameter',
                'expectedReturns' => [
                    'path' => ['null', 'string'],
                ],
            ],
            'override' => [
                'classFile' => dirname(__DIR__, 3) . '/Examples/UserResource.php',
                'className' => UserResource::class,
                'methodName' => 'override',
                'expectedReturns' => [
                    'id' => ['int'],
                ],
            ],
        ];
    }
}
