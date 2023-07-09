<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ResourceParserGenerator\Contracts\Converters\ParserTypeConverterContract;
use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFileFormatLocatorContract;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceMethodParserContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ParserData;
use ResourceParserGenerator\DataObjects\ParserDataCollection;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourceData;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Filesystem\ResourceFileLocator;
use ResourceParserGenerator\Generators\ParserConfigurationGenerator;
use Throwable;

class BuildResourceParsersCommand extends Command
{
    protected $signature = 'build:resource-parsers {--check} {--config=build.resource_parsers}';
    protected $description = 'Generate resource parsers based on the specified configuration.';

    public function handle(): int
    {
        // Load and validate the configuration.
        $configuration = $this->parseConfiguration(strval($this->option('config')));
        if (!$configuration) {
            return static::FAILURE;
        }

        $resourceParser = $this->resolve(ResourceMethodParserContract::class);
        $parsedResources = collect();

        // Parse the resources and their dependencies
        foreach ($configuration->parsers as $parserConfiguration) {
            try {
                $parsedResources = $resourceParser->parse(
                    $parserConfiguration->method[0],
                    $parserConfiguration->method[1],
                    $parsedResources,
                );
            } catch (Throwable $error) {
                $this->components->error(sprintf(
                    'Failed to generate parser for "%s::%s"',
                    $parserConfiguration->method[0],
                    $parserConfiguration->method[1],
                ));
                $this->components->bulletList([$error->getMessage()]);
                return static::FAILURE;
            }
        }

        $isChecking = (bool)$this->option('check');
        $returnValue = static::SUCCESS;

        $parserConfigurationGenerator = $this->resolve(ParserConfigurationGenerator::class);
        $parserTypeConverter = $this->resolve(ParserTypeConverterContract::class);
        $parserGenerator = $this->resolve(ParserGeneratorContract::class);

        // Convert the found resource types into parser types
        $parserCollection = $this->resolve(ParserDataCollection::class, [
            'parsers' => $parsedResources->map(fn(ResourceData $resource) => new ParserData(
                $resource,
                $parserConfigurationGenerator->generate($configuration, $resource->className, $resource->methodName),
                $resource->properties->map(fn(TypeContract $type) => $parserTypeConverter->convert($type)),
            ))
        ]);

        // Create a new context for the generation to control which parsers are being generated per file, then split up
        // the parsers by file and loop over generating each file after updating the local context.
        $generatorContext = $this->resolve(ParserGeneratorContextContract::class, [
            'parsers' => $parserCollection,
        ]);

        foreach ($parserCollection->splitToFiles() as $fileName => $localParsers) {
            $filePath = $configuration->outputPath . '/' . $fileName;

            try {
                $fileContents = $generatorContext->withLocalContext(
                    $localParsers,
                    fn() => $parserGenerator->generate($localParsers, $generatorContext),
                );
            } catch (Throwable $error) {
                $this->components->twoColumnDetail(
                    $isChecking
                        ? sprintf('Checking %s', $filePath)
                        : sprintf('Writing %s', $filePath),
                    'Error',
                );
                $this->components->bulletList([$error->getMessage()]);
                $returnValue = static::FAILURE;
                continue;
            }

            if ($isChecking) {
                $existingContents = File::exists($filePath) ? File::get($filePath) : null;
                $isMatch = $existingContents === $fileContents;

                $this->components->twoColumnDetail(
                    sprintf('Checking %s', $filePath),
                    $isMatch ? 'Up to date' : 'Out of date',
                );

                if (!$isMatch) {
                    $returnValue = static::FAILURE;
                }
            } else {
                $this->components->twoColumnDetail(
                    sprintf('Writing %s', $filePath),
                    sprintf('%s bytes', number_format(strlen($fileContents))),
                );

                File::put($filePath, $fileContents);
            }
        }

        return $returnValue;
    }

    private function parseConfiguration(string $configKey): ?ParserGeneratorConfiguration
    {
        $config = Config::get($configKey);
        if (!$config) {
            $this->components->error(
                sprintf('No configuration found at "%s" for resource parser generation.', $configKey),
            );
            return null;
        }

        $validator = Validator::make(
            Arr::wrap($config),
            [
                'output_path' => ['required', 'string'],
                'sources' => ['array'],
                'sources.*' => [
                    function ($key, $value, $fail) {
                        if (!($value instanceof ParserSourceContract)) {
                            $fail(sprintf(
                                'The %s field must be an instance of %s.',
                                $key,
                                ParserSourceContract::class,
                            ));
                        }
                    },
                ],
            ],
            [],
            [
                'output_path' => $configKey . '.output_path',
                'sources' => $configKey . '.sources',
            ],
        );

        $errors = $validator->errors();

        if (count($errors)) {
            $this->components->error('Errors found in configuration:');
            $this->components->bulletList($errors->all());
            return null;
        }

        $valid = $validator->valid();

        $outputPath = $valid['output_path'];
        if (!File::exists($outputPath)) {
            $this->components->error(sprintf('Output path "%s" does not exist.', $outputPath));
            return null;
        }

        /**
         * @var ParserConfiguration[] $sources
         */
        $sources = [];

        foreach ($valid['sources'] as $source) {
            if ($source instanceof ParserConfiguration) {
                $sources[] = $source;
            } elseif ($source instanceof ResourcePath) {
                $files = $this->resolve(ResourceFileLocator::class)->files($source);
                $formatLocator = $this->resolve(ResourceFileFormatLocatorContract::class);
                foreach ($files as $file) {
                    $formats = $formatLocator->formats($file);
                    foreach ($formats as $format) {
                        $sources[] = new ParserConfiguration($format);
                    }
                }
            } else {
                $this->components->error(sprintf('Unhandled source type "%s"', get_class($source)));
                return null;
            }
        }

        return new ParserGeneratorConfiguration($outputPath, ...$sources);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $parameters
     * @return T
     */
    private function resolve(string $class, array $parameters = [])
    {
        return resolve($class, $parameters);
    }
}
