<?php

declare(strict_types=1);

namespace ResourceParserGenerator;

use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ResourceParserGenerator\Commands\GenerateResourceParserCommand;
use ResourceParserGenerator\Filesystem\ClassFileFinder;

class ResourceParserGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'resource-parser-generator');
    }

    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->commands([
                GenerateResourceParserCommand::class,
            ]);

            $this->app->singleton(ClassFileFinder::class, fn() => new ClassFileFinder(
                strval(Env::get('COMPOSER_VENDOR_DIR')) ?: $this->app->basePath('vendor')
            ));
            $this->app->singleton(Parser::class, fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7));
            $this->app->singleton(DocBlockFactory::class, fn() => DocBlockFactory::createInstance());
        }
    }
}
