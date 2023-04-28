<?php

declare(strict_types=1);

namespace ResourceParserGenerator;

use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Filesystem\ClassFileLocator;

class ResourceParserGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'resource-parser-generator');
    }

    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->singleton(
                ClassFileLocatorContract::class,
                fn() => new ClassFileLocator(
                    strval(Env::get('COMPOSER_VENDOR_DIR')) ?: $this->app->basePath('vendor')
                ),
            );

            $this->app->singleton(
                Parser::class,
                fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7),
            );
        }
    }
}
