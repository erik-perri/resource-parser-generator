<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Console\Commands\GenerateResourceParsersCommand;
use ResourceParserGenerator\Tests\Examples\RelatedResource;
use ResourceParserGenerator\Tests\Examples\UserResource;
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

    public function testGeneratorShouldReturnFailureIfClassDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParsersCommand::class, [
            'output-path' => dirname(__DIR__, 3) . '/Output',
            'resource-class-method-spec' => 'ResourceParserGenerator\MissingClass::base',
        ])
            ->expectsOutputToContain('Class "ResourceParserGenerator\MissingClass" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfMethodDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParsersCommand::class, [
            'output-path' => dirname(__DIR__, 3) . '/Output',
            'resource-class-method-spec' => UserResource::class . '::notARealMethod',
        ])
            ->expectsOutputToContain(
                'Class "' . UserResource::class . '" does not contain a "notARealMethod" method.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfFileDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParsersCommand::class, [
            'output-path' => '/where/is/this/file',
            'resource-class-method-spec' => UserResource::class . '::adminList',
        ])
            ->expectsOutputToContain('Output path "/where/is/this/file" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    #[DataProvider('generatedContentProvider')]
    public function testGeneratorShouldReturnExpectedContent(
        array $methodSpecs,
        string $splitStrategy,
        array $expectedOutput,
    ): void {
        $outputPath = dirname(__DIR__, 3) . '/Output';

        $this->artisan(GenerateResourceParsersCommand::class, [
            'output-path' => $outputPath . ($splitStrategy === 'none' ? '/parsers.ts' : ''),
            'resource-class-method-spec' => $methodSpecs,
            '--split-strategy' => $splitStrategy,
        ])
            ->assertExitCode(0)
            ->execute();

        foreach ($expectedOutput as $file => $contents) {
            $this->assertEquals($contents, file_get_contents($outputPath . '/' . $file));
        }
    }

    public static function generatedContentProvider(): array
    {
        $examples = dirname(__DIR__, 3) . '/Examples/Output/Specific';
        return [
            'UserResource::adminList' => [
                'methodSpecs' => [UserResource::class . '::adminList'],
                'splitStrategy' => 'resource',
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents($examples . '/userResourceParsers-adminList.ts.txt'),
                ],
            ],
            'UserResource::authentication' => [
                'methodSpecs' => [UserResource::class . '::authentication'],
                'splitStrategy' => 'resource',
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents(
                        $examples . '/userResourceParsers-authentication.ts.txt',
                    ),
                ],
            ],
            'combined' => [
                'methodSpecs' => [
                    UserResource::class . '::authentication',
                    UserResource::class . '::adminList',
                    UserResource::class . '::ternaries',
                    UserResource::class . '::relatedResource',
                    RelatedResource::class . '::base',
                ],
                'splitStrategy' => 'none',
                'expectedOutput' => [
                    'parsers.ts' => file_get_contents($examples . '/userResourceParsers-combined.ts.txt'),
                ],
            ],
            'split' => [
                'methodSpecs' => [
                    UserResource::class . '::authentication',
                    UserResource::class . '::adminList',
                    UserResource::class . '::ternaries',
                    UserResource::class . '::relatedResource',
                    RelatedResource::class . '::base',
                ],
                'splitStrategy' => 'resource',
                'expectedOutput' => [
                    'userResourceParsers.ts' => file_get_contents($examples . '/userResourceParsers-split.ts.txt'),
                    'relatedResourceParsers.ts' => file_get_contents(
                        $examples . '/relatedResourceParsers-split.ts.txt',
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
