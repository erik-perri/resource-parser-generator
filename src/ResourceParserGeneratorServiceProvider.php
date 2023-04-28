<?php

declare(strict_types=1);

namespace ResourceParserGenerator;

use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use PhpParser\Parser;
use PhpParser\ParserFactory;

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
                Parser::class,
                fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7),
            );
        }
    }
}
