<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\ClassMethodReturnParser;
use ResourceParserGenerator\Tests\Examples\Enums\LegacyPostStatus;
use ResourceParserGenerator\Tests\Examples\Enums\Permission;
use ResourceParserGenerator\Tests\Examples\Enums\PostStatus;
use ResourceParserGenerator\Tests\Examples\Resources\Nested\RelatedResource;
use ResourceParserGenerator\Tests\Examples\Resources\PostResource;
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
            $result = $result->describeArray();
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
            'UserResource::variableHinted' => [
                'method' => [UserResource::class, 'variableHinted'],
                'expected' => [
                    'variable' => 'string',
                ],
            ],
            'UserResource::variableUnion' => [
                'method' => [UserResource::class, 'variableUnion'],
                'expected' => [
                    'variable' => 'int|string',
                ],
            ],
            'UserResource::matchedValue' => [
                'method' => [UserResource::class, 'matchedValue'],
                'expected' => [
                    'matched_value' => sprintf('%1$s::base|%1$s::simple', PostResource::class),
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
            'UserResource::usingCasts' => [
                'method' => [UserResource::class, 'usingCasts'],
                'expectedReturns' => [
                    'as_string' => 'string',
                    'as_int' => 'int',
                    'as_bool' => 'bool',
                    'as_bool_not' => 'bool',
                    'as_bool_and' => 'bool',
                    'as_bool_or' => 'bool',
                ],
            ],
            'UserResource::usingExplicitProperties' => [
                'method' => [UserResource::class, 'usingExplicitProperties'],
                'expectedReturns' => [
                    'date' => 'null|string',
                    'promoted' => 'string',
                ],
            ],
            'UserResource::usingParameter' => [
                'method' => [UserResource::class, 'usingParameter'],
                'expectedReturns' => [
                    'path' => 'null|string',
                ],
            ],
            'UserResource::usingWhen' => [
                'method' => [UserResource::class, 'usingWhen'],
                'expected' => [
                    'no_fallback' => 'string|undefined',
                    'with_fallback' => 'null|string',
                ],
            ],
            'UserResource::usingWhenLoaded' => [
                'method' => [UserResource::class, 'usingWhenLoaded'],
                'expected' => [
                    'no_fallback' => PostResource::class . '::simple|undefined',
                    'with_fallback' => 'string',
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
            'UserResource::enumMethods' => [
                'method' => [UserResource::class, 'enumMethods'],
                'expected' => [
                    'permissions' => 'Illuminate\Support\Collection<int, string>',
                ],
            ],
            'UserResource::enumWithoutValue' => [
                'method' => [UserResource::class, 'enumWithoutValue'],
                'expected' => [
                    'latestStatus' => 'enum<' . PostStatus::class . ', string>|undefined',
                    'legacyStatus' => 'enum<' . LegacyPostStatus::class . ', string>|undefined',
                ],
            ],
            'UserResource::usingCollectionPluck' => [
                'method' => [UserResource::class, 'usingCollectionPluck'],
                'expected' => [
                    'enum_all_without_pluck' => 'enum<' . Permission::class . ', string>[]',
                    'latest_post_ids' => 'int[]',
                    'permissions' => 'string[]',
                ],
            ],
            'UserResource::usingCollectionMap' => [
                'method' => [UserResource::class, 'usingCollectionMap'],
                'expected' => [
                    'permissions' => 'string[]',
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
                    'should_have_been_a_resource' => [
                        'id' => 'string',
                        'should_have_been_when_loaded' => 'null|' . PostResource::class . '::base',
                    ],
                ],
            ],
        ];
    }
}
