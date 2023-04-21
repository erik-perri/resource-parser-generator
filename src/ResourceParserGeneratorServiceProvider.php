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
    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->commands([
                GenerateResourceParserCommand::class,
            ]);

            $this->app->singleton(ClassFileFinder::class, fn() => new ClassFileFinder(
                Env::get('COMPOSER_VENDOR_DIR') ?: $this->app->basePath('vendor')
            ));
            $this->app->bind(Parser::class, fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7));
            $this->app->bind(DocBlockFactory::class, fn() => DocBlockFactory::createInstance());
        }
    }
}
