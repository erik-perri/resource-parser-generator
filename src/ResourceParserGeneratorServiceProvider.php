<?php

declare(strict_types=1);

namespace ResourceParserGenerator;

use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class ResourceParserGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->bind(Parser::class, fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7));
            $this->app->bind(DocBlockFactory::class, fn() => DocBlockFactory::createInstance());
        }
    }
}
