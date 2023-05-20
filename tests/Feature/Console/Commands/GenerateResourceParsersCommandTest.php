<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Feature\Console\Commands;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Console\Commands\GenerateResourceParsersCommand;
use ResourceParserGenerator\Tests\Examples\Resources\RelatedResource;
use ResourceParserGenerator\Tests\Examples\Resources\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(GenerateResourceParsersCommand::class)]
class GenerateResourceParsersCommandTest extends TestCase
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

    public function testGeneratorShouldReturnFailureIfConfigurationIsNotSet(): void
    {
        $this->artisan(GenerateResourceParsersCommand::class)
            ->expectsOutputToContain(
                'No configuration found at "build.resource_parsers" for resource parser generation.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfClassDoesNotExist(): void
    {
        Config::set('build.resource_parsers', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'parsers' => [
                [UserResource::class, 'adminList'],
                ['ResourceParserGenerator\MissingClass', 'base'],
            ],
        ]);

        $this->artisan(GenerateResourceParsersCommand::class)
            ->expectsOutputToContain('The parsers.1 field references unknown class ')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfMethodDoesNotExist(): void
    {
        Config::set('build.resource_parsers', [
            'output_path' => dirname(__DIR__, 3) . '/Output',
            'parsers' => [
                [UserResource::class, 'adminList'],
                [UserResource::class, 'notARealMethod'],
            ],
        ]);

        $this->artisan(GenerateResourceParsersCommand::class)
            ->expectsOutputToContain('The parsers.1 field references unknown method ')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfFileDoesNotExist(): void
    {
        Config::set('build.resource_parsers', [
            'output_path' => '/where/is/this/file',
            'parsers' => [
                [UserResource::class, 'adminList'],
            ],
        ]);

        $this->artisan(GenerateResourceParsersCommand::class)
            ->expectsOutputToContain('Output path "/where/is/this/file" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    #[DataProvider('generatedContentProvider')]
    public function testGeneratorShouldReturnExpectedContent(Closure $configFactory, array $expectedOutput): void
    {
        $outputPath = dirname(__DIR__, 3) . '/Output';
        $config = $configFactory->call($this, $outputPath);

        Config::set('build.resource_parsers', $config);

        $this->artisan(GenerateResourceParsersCommand::class)
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
            'UserResource::adminList not configured' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'parsers' => [
                        [UserResource::class, 'adminList'],
                    ],
                ],
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents(
                        $examples . '/UserResource-adminList-not-configured.ts.txt',
                    ),
                ],
            ],
            'UserResource::adminList configured' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'parsers' => [
                        [
                            'resource' => [UserResource::class, 'adminList'],
                            'output_file' => 'custom.ts',
                            'type' => 'CustomParser',
                            'variable' => 'customParser',
                        ],
                    ],
                ],
                'expectedOutput' => [
                    'custom.ts' => file_get_contents(
                        $examples . '/UserResource-adminList-configured.ts.txt',
                    ),
                ],
            ],
            'combined' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'parsers' => [
                        [
                            'resource' => [UserResource::class, 'adminList'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [UserResource::class, 'authentication'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [UserResource::class, 'combined'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [UserResource::class, 'ternaries'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [UserResource::class, 'relatedResource'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [RelatedResource::class, 'base'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [RelatedResource::class, 'shortFormatNotNamedLikeFormatName'],
                            'output_file' => 'parsers.ts',
                        ],
                        [
                            'resource' => [RelatedResource::class, 'verbose'],
                            'output_file' => 'parsers.ts',
                        ],
                    ],
                ],
                'expectedOutput' => [
                    'parsers.ts' => file_get_contents($examples . '/combined.ts.txt'),
                ],
            ],
            'split' => [
                'config' => fn(string $outputPath) => [
                    'output_path' => $outputPath,
                    'parsers' => [
                        [UserResource::class, 'adminList'],
                        [UserResource::class, 'authentication'],
                        [UserResource::class, 'combined'],
                        [UserResource::class, 'ternaries'],
                        [UserResource::class, 'relatedResource'],
                        [RelatedResource::class, 'base'],
                        [RelatedResource::class, 'shortFormatNotNamedLikeFormatName'],
                        [RelatedResource::class, 'verbose'],
                    ],
                ],
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents($examples . '/split/userResourceParsers.ts.txt'),
                    'relatedResourceParsers.ts' => file_get_contents(
                        $examples . '/split/relatedResourceParsers.ts.txt',
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
