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
use ResourceParserGenerator\Contexts\ParserGeneratorContext;
use ResourceParserGenerator\Contracts\Converters\DeclaredTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\DocBlockTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ExpressionTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ParamTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\ReflectionTypeConverterContract;
use ResourceParserGenerator\Contracts\Converters\VariableTypeConverterContract;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFileFormatLocatorContract;
use ResourceParserGenerator\Contracts\Generators\EnumGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\EnumNameGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Parsers\ClassConstFetchValueParserContract;
use ResourceParserGenerator\Contracts\Parsers\ClassMethodReturnParserContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Parsers\ExpressionValueParserContract;
use ResourceParserGenerator\Contracts\Parsers\PhpFileParserContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceMethodParserContract;
use ResourceParserGenerator\Converters\DeclaredTypeConverter;
use ResourceParserGenerator\Converters\DocBlockTypeConverter;
use ResourceParserGenerator\Converters\ExpressionTypeConverter;
use ResourceParserGenerator\Converters\ParamTypeConverter;
use ResourceParserGenerator\Converters\ParserTypeConverter;
use ResourceParserGenerator\Converters\ReflectionTypeConverter;
use ResourceParserGenerator\Converters\VariableTypeConverter;
use ResourceParserGenerator\Filesystem\ClassFileLocator;
use ResourceParserGenerator\Filesystem\ResourceFileFormatLocator;
use ResourceParserGenerator\Generators\EnumGenerator;
use ResourceParserGenerator\Generators\EnumNameGenerator;
use ResourceParserGenerator\Generators\ParserGenerator;
use ResourceParserGenerator\Generators\ParserNameGenerator;
use ResourceParserGenerator\Parsers\ClassConstFetchValueParser;
use ResourceParserGenerator\Parsers\ClassMethodReturnParser;
use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Parsers\DocBlockParser;
use ResourceParserGenerator\Parsers\ExpressionValueParser;
use ResourceParserGenerator\Parsers\PhpFileParser;
use ResourceParserGenerator\Parsers\ResourceMethodParser;
use RuntimeException;

class ResourceParserGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'resource-parser-generator');
        }
    }

    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            // Commands
            $this->commands([
                Console\Commands\BuildResourceParsersCommand::class,
            ]);

            // Dependencies
            $this->app->singleton(Parser::class, fn() => (new ParserFactory)->create(ParserFactory::ONLY_PHP7));
            $this->app->singleton(NodeFinder::class);
            $this->app->singleton(Lexer::class);
            $this->app->singleton(PhpDocParser::class, function () {
                $constExprParser = new ConstExprParser();
                return new PhpDocParser(new TypeParser($constExprParser), $constExprParser);
            });

            // Converters
            $this->app->singleton(DeclaredTypeConverterContract::class, DeclaredTypeConverter::class);
            $this->app->singleton(DocBlockTypeConverterContract::class, DocBlockTypeConverter::class);
            $this->app->singleton(ExpressionTypeConverterContract::class, ExpressionTypeConverter::class);
            $this->app->singleton(ParamTypeConverterContract::class, ParamTypeConverter::class);
            $this->app->singleton(ParserTypeConverterContract::class, ParserTypeConverter::class);
            $this->app->singleton(ReflectionTypeConverterContract::class, ReflectionTypeConverter::class);
            $this->app->singleton(VariableTypeConverterContract::class, VariableTypeConverter::class);

            // Data Objects
            $this->app->bind(ParserGeneratorContextContract::class, ParserGeneratorContext::class);

            // Generators
            $this->app->singleton(EnumNameGeneratorContract::class, EnumNameGenerator::class);
            $this->app->singleton(EnumGeneratorContract::class, EnumGenerator::class);
            $this->app->singleton(ParserNameGeneratorContract::class, ParserNameGenerator::class);
            $this->app->singleton(ParserGeneratorContract::class, ParserGenerator::class);

            // Locators
            $this->app->singleton(
                ClassFileLocatorContract::class,
                function () {
                    $composerVendorOverride = Env::get('COMPOSER_VENDOR_DIR');
                    if ($composerVendorOverride && !is_string($composerVendorOverride)) {
                        throw new RuntimeException(
                            'The COMPOSER_VENDOR_DIR environment variable must be a string.',
                        );
                    }

                    return new ClassFileLocator($composerVendorOverride ?: $this->app->basePath('vendor'));
                },
            );
            $this->app->singleton(ResourceFileFormatLocatorContract::class, ResourceFileFormatLocator::class);

            // Parsers
            $this->app->singleton(ClassConstFetchValueParserContract::class, ClassConstFetchValueParser::class);
            $this->app->singleton(ClassMethodReturnParserContract::class, ClassMethodReturnParser::class);
            $this->app->singleton(ClassParserContract::class, ClassParser::class);
            $this->app->singleton(DocBlockParserContract::class, DocBlockParser::class);
            $this->app->singleton(ExpressionValueParserContract::class, ExpressionValueParser::class);
            $this->app->singleton(PhpFileParserContract::class, PhpFileParser::class);
            $this->app->singleton(ResourceMethodParserContract::class, ResourceMethodParser::class);
        }
    }
}
