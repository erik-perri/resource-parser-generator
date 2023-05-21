<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\ClassMethodReturnParser;
use ResourceParserGenerator\Tests\Examples\Resources\PostResource;
use ResourceParserGenerator\Tests\Examples\Resources\RelatedResource;
use ResourceParserGenerator\Tests\Examples\Resources\UserResource;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Types\ArrayWithPropertiesType;

#[CoversClass(ClassMethodReturnParser::class)]
class ClassMethodReturnParserTest extends TestCase
{
    #[DataProvider('methodReturnProvider')]
    public function testParsesClassMethodReturn(array $method, array|string $expected): void
    {
        // Arrange
        $parser = $this->make(ClassMethodReturnParser::class);
        [$className, $methodName] = $method;

        // Act
        $result = $parser->parse($className, $methodName);

        if ($result instanceof ArrayWithPropertiesType) {
            $result = $result->describeRecursive();
        } else {
            $result = $result->describe();
        }

        // Assert
        $this->assertEquals($expected, $result);
    }

    public static function methodReturnProvider(): array
    {
        return [
            'UserResource::base' => [
                'method' => [UserResource::class, 'base'],
                'expected' => [
                    'id' => 'string',
                    'email' => 'string',
                    'created_at' => 'null|string',
                ],
            ],
            'UserResource::combined' => [
                'method' => [UserResource::class, 'combined'],
                'expected' => [
                    'email' => 'null|string',
                    'name' => 'string|undefined',
                ],
            ],
            'UserResource::ternaries' => [
                'method' => [UserResource::class, 'ternaries'],
                'expected' => [
                    'ternary_to_int' => 'int',
                    'ternary_to_compound' => 'bool|int|string',
                    'short_ternary' => 'string',
                ],
            ],
            'UserResource::scalars' => [
                'method' => [UserResource::class, 'scalars'],
                'expectedReturns' => [
                    'string' => 'string',
                    'negative_number' => 'int',
                    'positive_number' => 'int',
                    'neutral_number' => 'int',
                    'float' => 'float',
                    'boolean_true' => 'bool',
                    'boolean_false' => 'bool',
                    'null' => 'null',
                ],
            ],
            'UserResource::usingParameter' => [
                'method' => [UserResource::class, 'usingParameter'],
                'expectedReturns' => [
                    'path' => 'null|string',
                ],
            ],
            'UserResource::usingWhenLoaded' => [
                'method' => [UserResource::class, 'usingWhenLoaded'],
                'expected' => [
                    'related' => PostResource::class . '::simple|undefined',
                ],
            ],
            'UserResource::usingWhenLoadedFallback' => [
                'method' => [UserResource::class, 'usingWhenLoadedFallback'],
                'expected' => [
                    'related' => 'string',
                ],
            ],
            'UserResource::staticCallOrConst' => [
                'method' => [UserResource::class, 'staticCallOrConst'],
                'expected' => [
                    'const_float' => 'float',
                    'const_string' => 'string',
                    'explicit_method' => 'int',
                    'hinted_method' => 'string',
                    'reflected_const' => 'int',
                    'reflected_method' => 'array',
                ],
            ],
            'UserResource::relatedResource' => [
                'method' => [UserResource::class, 'relatedResource'],
                'expected' => [
                    'with_format_default' => RelatedResource::class . '::base',
                    'with_format_short' => RelatedResource::class . '::shortFormatNotNamedLikeFormatName',
                    'with_format_verbose' => RelatedResource::class . '::verbose',
                ],
            ],
            'UserResource::usingResourceCollection' => [
                'method' => [UserResource::class, 'usingResourceCollection'],
                'expected' => [
                    'posts' => PostResource::class . '::simple[]',
                ],
            ],
            'UserResource::childArrays' => [
                'method' => [UserResource::class, 'childArrays'],
                'expected' => [
                    'should_have_been_a_resource' => 'array|'
                        . 'array<{id: string; should_have_been_when_loaded: null|' . PostResource::class . '::base}>',
                ],
            ],
        ];
    }
}
