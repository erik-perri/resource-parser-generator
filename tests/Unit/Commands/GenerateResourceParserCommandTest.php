<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Commands;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Commands\GenerateResourceParserCommand;
use ResourceParserGenerator\Tests\Stubs\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(GenerateResourceParserCommand::class)]
class GenerateResourceParserCommandTest extends TestCase
{
    public function testGeneratorShouldReturnFailureIfClassDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => 'ResourceParserGenerator\MissingClass',
            'methodName' => 'base',
        ])
            ->expectsOutputToContain('Class "ResourceParserGenerator\MissingClass" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfMethodDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => UserResource::class,
            'methodName' => 'notARealMethod',
        ])
            ->expectsOutputToContain(
                'Class "' . UserResource::class . '" does not contain a "notARealMethod" method.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    #[DataProvider('generatedContentProvider')]
    public function testGeneratorShouldReturnExpectedContent(
        string $className,
        string $methodName,
        Closure $outputFactory
    ): void {
        $template = $outputFactory->call($this);

        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassName' => $className,
            'methodName' => $methodName,
        ])
            ->expectsOutput($template)
            ->assertExitCode(0)
            ->execute();
    }

    public static function generatedContentProvider(): array
    {
        return [
            'UserResource authentication format' => [
                'className' => UserResource::class,
                'methodName' => 'authentication',
                'outputFactory' => fn() => file_get_contents(
                    dirname(__DIR__, 2) . '/Stubs/userResourceAuthenticationParser.ts.stub',
                ),
            ],
            'UserResource adminList format' => [
                'className' => UserResource::class,
                'methodName' => 'adminList',
                'outputFactory' => fn() => file_get_contents(
                    dirname(__DIR__, 2) . '/Stubs/userResourceAdminListParser.ts.stub',
                ),
            ],
        ];
    }
}
