<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Feature\Console\Commands;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Console\Commands\BuildResourceParsersCommand;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Tests\Examples\Resources\Nested\RelatedResource;
use ResourceParserGenerator\Tests\Examples\Resources\PostResource;
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

        file_put_contents($outputPath . '/userResourceParsers.ts', '// Out of date content');

        $this->artisan(BuildResourceParsersCommand::class, ['--check' => true])
            ->assertExitCode(1)
            ->execute();

        $this->assertEquals(
            '// Out of date content',
            file_get_contents($outputPath . '/userResourceParsers.ts'),
        );
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
            'UserResource::base not configured' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'base']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents(
                        $examples . '/UserResource-base-not-configured.ts.txt',
                    ),
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
                        $examples . '/UserResource-base-configured.ts.txt',
                    ),
                ],
            ],
            'combined' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'base'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [UserResource::class, 'combined'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [UserResource::class, 'ternaries'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [UserResource::class, 'relatedResource'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [RelatedResource::class, 'base'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [RelatedResource::class, 'shortFormatNotNamedLikeFormatName'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [RelatedResource::class, 'verbose'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => file_get_contents($examples . '/combined.ts.txt'),
                ],
            ],
            'split' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration([UserResource::class, 'base']),
                        new ParserConfiguration([UserResource::class, 'combined']),
                        new ParserConfiguration([UserResource::class, 'ternaries']),
                        new ParserConfiguration([UserResource::class, 'relatedResource']),
                        new ParserConfiguration([RelatedResource::class, 'base']),
                        new ParserConfiguration([RelatedResource::class, 'shortFormatNotNamedLikeFormatName']),
                        new ParserConfiguration([RelatedResource::class, 'verbose']),
                    ],
                ],
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents($examples . '/split/userResourceParsers.ts.txt'),
                    'relatedResourceParsers.ts' => file_get_contents(
                        $examples . '/split/relatedResourceParsers.ts.txt',
                    ),
                ],
            ],
            'using path' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ResourcePath(dirname(__DIR__, 3) . '/Examples/Resources'),
                    ],
                ],
                'expectedOutput' => [
                    'postResourceParsers.ts' => file_get_contents($examples . '/split/postResourceParsers.ts.txt'),
                    'relatedResourceParsers.ts' => file_get_contents(
                        $examples . '/split/relatedResourceParsers.ts.txt',
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
                    'userResourceParsers.ts' => <<<TS
import {postResourceBaseParser} from './postResourceParsers';
import {nullable, object, output, string} from 'zod';

export const userResourceChildArraysParser = object({
  should_have_been_a_resource: object({should_have_been_when_loaded: nullable(postResourceBaseParser), id: string()}),
});

export type UserResourceChildArrays = output<typeof userResourceChildArraysParser>;

TS,
                ],
            ],
            'UserResource::enumMethods' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'enumMethods'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => <<<TS
import {array, object, output, string} from 'zod';

export const userResourceEnumMethodsParser = object({
  permissions: array(string()),
});

export type UserResourceEnumMethods = output<typeof userResourceEnumMethodsParser>;

TS,
                ],
            ],
            'UserResource::unknownComments' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'unknownComments'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => <<<TS
import {object, output, unknown} from 'zod';

export const userResourceUnknownCommentsParser = object({
  /**
   * Error: Unknown property "what" in "User"
   */
  propertyName: unknown(),
});

export type UserResourceUnknownComments = output<typeof userResourceUnknownCommentsParser>;

TS,
                ],
            ],
            'UserResource::usingWhenLoaded' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'sources' => [
                        new ParserConfiguration(
                            [UserResource::class, 'usingWhenLoaded'],
                            'parsers.ts',
                        ),
                        new ParserConfiguration(
                            [PostResource::class, 'simple'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => <<<TS
import {object, optional, output, string} from 'zod';

export const postResourceSimpleParser = object({
  status: string(),
});

export type PostResourceSimple = output<typeof postResourceSimpleParser>;

export const userResourceUsingWhenLoadedParser = object({
  no_fallback: optional(postResourceSimpleParser),
  with_fallback: string(),
});

export type UserResourceUsingWhenLoaded = output<typeof userResourceUsingWhenLoadedParser>;

TS,
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
                        new ParserConfiguration(
                            [PostResource::class, 'simple'],
                            'parsers.ts',
                        ),
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => <<<TS
import {array, object, output, string} from 'zod';

export const postResourceSimpleParser = object({
  status: string(),
});

export type PostResourceSimple = output<typeof postResourceSimpleParser>;

export const userResourceUsingResourceCollectionParser = object({
  posts: array(postResourceSimpleParser),
});

export type UserResourceUsingResourceCollection = output<typeof userResourceUsingResourceCollectionParser>;

TS,
                ],
            ],
        ];
    }

    private function clearTestOutputFiles(): void
    {
        File::delete(File::glob(dirname(__DIR__, 3) . '/Output/*'));
    }
}
