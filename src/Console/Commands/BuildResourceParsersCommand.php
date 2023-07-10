<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFileFormatLocatorContract;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ParserData;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Filesystem\ResourceFileLocator;
use ResourceParserGenerator\Processors\EnumConfigurationProcessor;
use ResourceParserGenerator\Processors\ParserConfigurationProcessor;
use ResourceParserGenerator\Processors\ResourceConfigurationProcessor;
use RuntimeException;
use Throwable;

class BuildResourceParsersCommand extends Command
{
    protected $signature = 'build:resource-parsers {--check} {--config=build.resource_parsers}';
    protected $description = 'Generate resource parsers based on the specified configuration.';

    public function handle(): int
    {
        $configuration = $this->parseConfiguration(strval($this->option('config')));
        if (!$configuration) {
            return static::FAILURE;
        }

        try {
            $resources = $this->resolve(ResourceConfigurationProcessor::class)->process($configuration);
            $enums = $this->resolve(EnumConfigurationProcessor::class)->process($configuration, $resources);
            $parsers = $this->resolve(ParserConfigurationProcessor::class)
                ->process($configuration, $resources, $enums);

            $parsersByFile = $parsers->groupBy(function (ParserData $data) {
                if (!$data->configuration->parserFile) {
                    throw new RuntimeException(sprintf(
                        'Could not find output file path for "%s::%s"',
                        $data->configuration->method[0],
                        $data->configuration->method[1],
                    ));
                }

                return $data->configuration->parserFile;
            });
        } catch (Throwable $error) {
            $this->components->error('Failed to parse resources.');
            $this->components->bulletList(array_filter([$error->getMessage(), $error->getPrevious()?->getMessage()]));
            return static::FAILURE;
        }

        // Create a new context for the generation to control which parsers are being generated per file, then split up
        // the parsers by file and loop over generating each file after updating the local context.
        $generatorContext = $this->resolve(ParserGeneratorContextContract::class, ['parsers' => $parsers]);
        $parserGenerator = $this->resolve(ParserGeneratorContract::class);
        $returnValue = static::SUCCESS;

        foreach ($parsersByFile as $fileName => $localParsers) {
            $filePath = $configuration->outputPath . '/' . $fileName;

            try {
                $fileContents = $generatorContext->withLocalContext(
                    $localParsers,
                    fn() => $parserGenerator->generate($localParsers, $generatorContext),
                );
            } catch (Throwable $error) {
                $this->components->twoColumnDetail(
                    $this->isChecking()
                        ? sprintf('Checking %s', $filePath)
                        : sprintf('Writing %s', $filePath),
                    'Error',
                );
                $this->components->bulletList([$error->getMessage()]);
                $returnValue = static::FAILURE;
                continue;
            }

            if ($this->isChecking()) {
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

    private function isChecking(): bool
    {
        return (bool)$this->option('check');
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
