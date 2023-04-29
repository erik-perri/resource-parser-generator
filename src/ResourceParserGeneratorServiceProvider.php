<?php

declare(strict_types=1);

namespace ResourceParserGenerator;

use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ResourceParserGenerator\Contracts\ClassFileLocatorContract;
use ResourceParserGenerator\Filesystem\ClassFileLocator;
use ResourceParserGenerator\Resolvers\ClassNameResolver;

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

            $this->app->scoped(ClassNameResolver::class);

            $this->app->singleton(
                Parser::class,
                fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7),
            );

            $this->app->singleton(
                NodeFinder::class,
                fn() => new NodeFinder,
            );

            $this->app->singleton(Lexer::class);
            $this->app->singleton(PhpDocParser::class, function () {
                $constExprParser = new ConstExprParser();
                return new PhpDocParser(
                    new TypeParser($constExprParser),
                    $constExprParser,
                );
            });
        }
    }
}
