<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
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

    private function clearTestOutputFiles(): void
    {
        File::delete(File::glob(dirname(__DIR__, 3) . '/Output/*'));
    }
}
