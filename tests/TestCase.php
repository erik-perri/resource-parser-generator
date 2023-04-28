<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests;

use ResourceParserGenerator\ResourceParserGeneratorServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set the app base path to get access to the autoloader for class file name resolution.
        $this->app->setBasePath(dirname(__DIR__));
    }

    protected function getPackageProviders($app): array
    {
        return [
            ResourceParserGeneratorServiceProvider::class,
        ];
    }

    /**
     * @template T
     *
     * @param class-string<T> $name
     * @param array $parameters
     * @return T
     */
    protected function make(string $name, array $parameters = []): mixed
    {
        return $this->app->make($name, $parameters);
    }
}
