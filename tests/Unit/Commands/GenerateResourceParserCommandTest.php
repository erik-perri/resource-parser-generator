<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Commands;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Commands\GenerateResourceParserCommand;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(GenerateResourceParserCommand::class)]
class GenerateResourceParserCommandTest extends TestCase
{
    public function testGeneratorShouldReturnFailureIfClassDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassMethodSpec' => 'ResourceParserGenerator\MissingClass::base',
        ])
            ->expectsOutputToContain('Class "ResourceParserGenerator\MissingClass" does not exist.')
            ->assertExitCode(1)
            ->execute();
    }

    public function testGeneratorShouldReturnFailureIfMethodDoesNotExist(): void
    {
        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassMethodSpec' => UserResource::class . '::notARealMethod',
        ])
            ->expectsOutputToContain(
                'Class "' . UserResource::class . '" does not contain a "notARealMethod" method.',
            )
            ->assertExitCode(1)
            ->execute();
    }

    #[DataProvider('generatedContentProvider')]
    public function testGeneratorShouldReturnExpectedContent(
        array $methodSpecs,
        Closure $outputFactory
    ): void {
        $template = $outputFactory->call($this);

        $this->artisan(GenerateResourceParserCommand::class, [
            'resourceClassMethodSpec' => $methodSpecs,
        ])
            ->expectsOutput($template)
            ->assertExitCode(0)
            ->execute();
    }

    public static function generatedContentProvider(): array
    {
        return [
            'UserResource authentication format' => [
                'methodSpecs' => [UserResource::class . '::authentication'],
                'outputFactory' => fn() => file_get_contents(
                    dirname(__DIR__, 2) . '/Output/userResourceAuthenticationParser.ts.txt',
                ),
            ],
            'UserResource adminList format' => [
                'methodSpecs' => [UserResource::class . '::adminList'],
                'outputFactory' => fn() => file_get_contents(
                    dirname(__DIR__, 2) . '/Output/userResourceAdminListParser.ts.txt',
                ),
            ],
            'combined file' => [
                'methodSpecs' => [
                    UserResource::class . '::authentication',
                    UserResource::class . '::adminList',
                ],
                'outputFactory' => fn() => file_get_contents(
                    dirname(__DIR__, 2) . '/Output/userResourceParser.ts.txt',
                ),
            ],
        ];
    }
}
