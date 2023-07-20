<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Feature\Console\Commands;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Console\Commands\BuildResourceParsersCommand;
use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumConfiguration;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Tests\Examples\Enums\Permission;
use ResourceParserGenerator\Tests\Examples\Enums\PostStatus;
use ResourceParserGenerator\Tests\Examples\Resources\UserResource;
use ResourceParserGenerator\Tests\TestCase;

class BuildResourceParsersCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearTestOutputFiles();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->clearTestOutputFiles();
    }

    public function testShouldReturnFailureIfConfigurationIsNotSet(): void
    {
        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain(
                'No configuration found at "build.resources" for resource parser generation.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldReturnFailureIfClassDoesNotExist(): void
    {
        Config::set('build.enums', ['output_path' => dirname(__DIR__, 3) . '/Output']);
        Config::set('build.resources', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base']),
                new ParserConfiguration(['ResourceParserGenerator\MissingClass', 'base']),
            ],
        ]);

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Could not find file for class ')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldReturnFailureIfMethodDoesNotExist(): void
    {
        Config::set('build.enums', ['output_path' => dirname(__DIR__, 3) . '/Output']);
        Config::set('build.resources', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base']),
                new ParserConfiguration([UserResource::class, 'notARealMethod']),
            ],
        ]);

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Unknown method "notARealMethod" in class ')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldReturnFailureIfFileDoesNotExist(): void
    {
        Config::set('build.enums', ['output_path' => dirname(__DIR__, 3) . '/Output']);
        Config::set('build.resources', [
            'output_path' => '/where/is/this/file',
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base']),
            ],
        ]);

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Output path "/where/is/this/file" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldFailWhenCheckSwitchFails(): void
    {
        $outputPath = dirname(__DIR__, 3) . '/Output';
        $config = [
            'output_path' => $outputPath,
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base']),
            ],
        ];

        Config::set('build.enums', ['output_path' => $outputPath]);
        Config::set('build.resources', $config);

        $this->artisan(BuildResourceParsersCommand::class)
            ->assertExitCode(0)
            ->execute();

        $this->artisan(BuildResourceParsersCommand::class, ['--check' => true])
            ->assertExitCode(0)
            ->execute();

        file_put_contents($outputPath . '/userResourceBaseParser.ts', '// Out of date content');

        $this->artisan(BuildResourceParsersCommand::class, ['--check' => true])
            ->assertExitCode(1)
            ->execute();

        $this->assertEquals(
            '// Out of date content',
            file_get_contents($outputPath . '/userResourceBaseParser.ts'),
        );
    }

    public function testShouldFailWhenMultipleParsersAreConfiguredWithTheSameFile(): void
    {
        Config::set('build.enums', ['output_path' => dirname(__DIR__, 3) . '/Output']);
        Config::set('build.resources', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base'], parserFile: 'user.ts'),
                new ParserConfiguration([UserResource::class, 'relatedResource'], parserFile: 'user.ts'),
            ],
        ]);

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Duplicate parser file "user.ts" configured.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldFailWhenMultipleParsersAreGeneratedWithTheSameFile(): void
    {
        Config::set('build.enums', ['output_path' => dirname(__DIR__, 3) . '/Output']);
        Config::set('build.resources', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new ParserConfiguration([UserResource::class, 'base']),
                new ParserConfiguration([UserResource::class, 'relatedResource']),
            ],
        ]);

        $mock = $this->mock(ParserNameGeneratorContract::class);
        $mock->expects('generateTypeName')
            ->andReturn('Parser')
            ->zeroOrMoreTimes();
        $mock->expects('generateVariableName')
            ->andReturn('parser')
            ->zeroOrMoreTimes();
        $mock->expects('generateFileName')
            ->andReturn('user.ts')
            ->zeroOrMoreTimes();

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Multiple parsers found while generating "user.ts"')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldFailWhenMultipleEnumsAreConfiguredWithTheSameFile(): void
    {
        Config::set('build.enums', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new EnumConfiguration(PostStatus::class, enumFile: 'enum.ts'),
                new EnumConfiguration(Permission::class, enumFile: 'enum.ts'),
            ],
        ]);
        Config::set('build.resources', ['output_path' => dirname(__DIR__, 3) . '/Output']);

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Duplicate enum file "enum.ts" configured.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testShouldFailWhenMultipleEnumsAreGeneratedWithTheSameFile(): void
    {
        Config::set('build.enums', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'sources' => [
                new EnumConfiguration(PostStatus::class),
                new EnumConfiguration(Permission::class),
            ],
        ]);
        Config::set('build.resources', ['output_path' => dirname(__DIR__, 3) . '/Output']);

        $mock = $this->mock(EnumNameGeneratorContract::class);
        $mock->expects('generateTypeName')
            ->andReturn('Enum')
            ->zeroOrMoreTimes();
        $mock->expects('generateFileName')
            ->andReturn('enum.ts')
            ->zeroOrMoreTimes();

        $this->artisan(BuildResourceParsersCommand::class)
            ->expectsOutputToContain('Multiple enums found while generating "enum.ts"')
            ->assertExitCode(1)
            ->execute();
    }

    #[DataProvider('generatedContentProvider')]
    public function testShouldReturnExpectedContent(Closure $configFactory, array $expectedOutput): void
    {
        $outputPath = dirname(__DIR__, 3) . '/Output';
        $config = $configFactory->call($this, $outputPath);

        Config::set('build.enums', ['output_path' => $outputPath]);
        Config::set('build.resources', $config);

        $this->artisan(BuildResourceParsersCommand::class)
            ->assertExitCode(0)
            ->execute();

        foreach ($expectedOutput as $file => $contents) {
            $this->assertEquals($contents, file_get_contents($outputPath . '/' . $file));
        }
    }

    public static function generatedContentProvider(): array
    {
        $examples = dirname(__DIR__, 3) . '/Examples/Generated';

        return [
            'UserResource::base' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'base']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceBaseParser.ts' => file_get_contents($examples . '/userResourceBaseParser.ts.txt'),
                ],
            ],
            'UserResource::base configured' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'base'],
                            'custom.ts',
                            'CustomParser',
                            'customParser',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'custom.ts' => file_get_contents(
                        $examples . '/userResourceBaseParser-custom.ts.txt',
                    ),
                ],
            ],
            'UserResource::childArrays' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'childArrays']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceChildArraysParser.ts' => file_get_contents(
                        $examples . '/userResourceChildArraysParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::combined' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'combined']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceCombinedParser.ts' => file_get_contents(
                        $examples . '/userResourceCombinedParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::enumMethods' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'enumMethods'],
                            'parser.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parser.ts' => file_get_contents(
                        $examples . '/userResourceEnumMethodsParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::relatedResource' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'relatedResource']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceRelatedResourceParser.ts' => file_get_contents(
                        $examples . '/userResourceRelatedResourceParser.ts.txt',
                    ),
                    'relatedResourceBaseParser.ts' => file_get_contents(
                        $examples . '/relatedResourceBaseParser.ts.txt',
                    ),
                    'relatedResourceShortFormatNotNamedLikeFormatNameParser.ts' => file_get_contents(
                        $examples . '/relatedResourceShortFormatNotNamedLikeFormatNameParser.ts.txt',
                    ),
                    'relatedResourceVerboseParser.ts' => file_get_contents(
                        $examples . '/relatedResourceVerboseParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::ternaries' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'ternaries']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceTernariesParser.ts' => file_get_contents(
                        $examples . '/userResourceTernariesParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::unknownComments' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'unknownComments'],
                            'parser.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parser.ts' => file_get_contents(
                        $examples . '/userResourceUnknownCommentsParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::usingResourceCollection' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'usingResourceCollection'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => file_get_contents(
                        $examples . '/userResourceUsingResourceCollectionParser.ts.txt',
                    ),
                ],
            ],
            'UserResource::usingWhenLoaded' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'usingWhenLoaded'],
                            'parser.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parser.ts' => file_get_contents(
                        $examples . '/userResourceUsingWhenLoadedParser.ts.txt',
                    ),
                ],
            ],
            'configured with ResourcePath' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ResourcePath(dirname(__DIR__, 3) . '/Examples/Resources'),
                    ],
                ],
                'expectedOutput' => [
                    'postResourceBaseParser.ts' => file_get_contents(
                        $examples . '/postResourceBaseParser.ts.txt',
                    ),
                    'postResourceSimpleParser.ts' => file_get_contents(
                        $examples . '/postResourceSimpleParser.ts.txt',
                    ),
                    'relatedResourceBaseParser.ts' => file_get_contents(
                        $examples . '/relatedResourceBaseParser.ts.txt',
                    ),
                    'relatedResourceShortFormatNotNamedLikeFormatNameParser.ts' => file_get_contents(
                        $examples . '/relatedResourceShortFormatNotNamedLikeFormatNameParser.ts.txt',
                    ),
                    'relatedResourceVerboseParser.ts' => file_get_contents(
                        $examples . '/relatedResourceVerboseParser.ts.txt',
                    ),
                ],
            ],
        ];
    }

    private function clearTestOutputFiles(): void
    {
        File::delete(File::glob(dirname(__DIR__, 3) . '/Output/*'));
    }
}
