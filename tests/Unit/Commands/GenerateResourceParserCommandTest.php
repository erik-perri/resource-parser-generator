<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ResourceParserGenerator\Commands\GenerateResourceParserCommand;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(GenerateResourceParserCommand::class)]
#[UsesClass(ResourceParserGeneratorServiceProvider::class)]
class GenerateResourceParserCommandTest extends TestCase
{
    private const USER_RESOURCE_MOCK = '/var/www/html/app/Http/Resources/UserResource.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ClassFileFinder::class)
            ->shouldReceive('find')
            ->with(UserResource::class)
            ->andReturn(self::USER_RESOURCE_MOCK);
    }

    public function testGeneratorShouldReturnFailureIfClassDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => 'ResourceParserGenerator\MissingClass',
            'formatMethod' => 'base',
        ])
            ->expectsOutputToContain('Class "ResourceParserGenerator\MissingClass" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfMethodDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => UserResource::class,
            'formatMethod' => 'notARealMethod',
        ])
            ->expectsOutputToContain(
                'Class "' . UserResource::class . '" does not contain a "notARealMethod" method.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnExpectedParser(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => UserResource::class,
            'formatMethod' => 'authentication',
        ])
            ->expectsOutputToContain(
                'Found class "' . UserResource::class . '" in file "' . self::USER_RESOURCE_MOCK . '"',
            )
            ->assertExitCode(0)
            ->execute();
    }
}
