<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ResourceParserGenerator\Contracts\Generators\EnumGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumData;
use ResourceParserGenerator\DataObjects\EnumGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ParserData;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\Exceptions\ConfigurationParserException;
use ResourceParserGenerator\Parsers\EnumGeneratorConfigurationParser;
use ResourceParserGenerator\Parsers\ParserGeneratorConfigurationParser;
use ResourceParserGenerator\Processors\EnumConfigurationProcessor;
use ResourceParserGenerator\Processors\ParserConfigurationProcessor;
use ResourceParserGenerator\Processors\ResourceConfigurationProcessor;
use RuntimeException;
use Throwable;

class BuildResourceParsersCommand extends Command
{
    protected $signature = 'build:resource-parsers {--check}
                                                   {--enum-config=build.enums}
                                                   {--parser-config=build.resources}';
    protected $description = 'Generate resource parsers based on the specified configuration.';

    public function handle(): int
    {
        try {
            $parserConfiguration = $this->resolve(ParserGeneratorConfigurationParser::class)->parse(
                strval($this->option('parser-config')),
            );
            $enumConfiguration = $this->resolve(EnumGeneratorConfigurationParser::class)->parse(
                strval($this->option('enum-config')),
            );
        } catch (ConfigurationParserException $error) {
            $this->components->error($error->getMessage());
            if ($error->errors) {
                $this->components->bulletList($error->errors);
            }
            return static::FAILURE;
        } catch (Throwable $error) {
            $this->components->error('Failed to parse configuration.');
            $this->components->bulletList([$error->getMessage()]);
            return static::FAILURE;
        }

        if (!$parserConfiguration->outputPath && !$enumConfiguration->outputPath) {
            $this->components->error(sprintf(
                'No configuration found at "%s" or "%s" for generation.',
                $this->option('parser-config'),
                $this->option('enum-config'),
            ));
            return static::FAILURE;
        }

        if ($parserConfiguration->parsers->isEmpty() && $enumConfiguration->enums->isEmpty()) {
            $this->components->info('No resources found to generate.');
            return static::FAILURE;
        }

        try {
            $resources = $this->resolve(ResourceConfigurationProcessor::class)->process($parserConfiguration);
            $enums = $this->resolve(EnumConfigurationProcessor::class)->process($enumConfiguration, $resources);
            $parsers = $this->resolve(ParserConfigurationProcessor::class)
                ->process($parserConfiguration, $resources, $enums);
        } catch (Throwable $error) {
            $this->components->error('Failed to parse resources.');
            $this->components->bulletList(array_filter([$error->getMessage(), $error->getPrevious()?->getMessage()]));
            return static::FAILURE;
        }

        $returnValue = static::SUCCESS;

        try {
            $returnValue = $this->generateEnums($enumConfiguration, $enums, $returnValue);
            $returnValue = $this->generateParsers($parserConfiguration, $parsers, $returnValue);
        } catch (Throwable $error) {
            $this->components->error('Failed to generate resources.');
            $this->components->bulletList([$error->getMessage()]);
            return static::FAILURE;
        }

        return $returnValue;
    }

    /**
     * @param EnumGeneratorConfiguration $enumConfiguration
     * @param Collection<int, EnumData> $enums
     * @param int $returnValue
     * @return int
     */
    private function generateEnums(
        EnumGeneratorConfiguration $enumConfiguration,
        Collection $enums,
        int $returnValue,
    ): int {
        if ($enums->isEmpty()) {
            return $returnValue;
        }

        if (!$enumConfiguration->outputPath) {
            throw new RuntimeException(sprintf(
                'Found %d %s to generate but no output path was specified.',
                $enums->count(),
                Str::plural('enum', $enums->count()),
            ));
        }

        $this->components->info(
            sprintf('Processing %d %s', $enums->count(), Str::plural('enum', $enums->count())),
        );

        /**
         * @var Collection<string, EnumData> $enumsByFile
         */
        $enumsByFile = $enums
            ->collect()
            ->groupBy(fn(EnumData $data) => $data->configuration->enumFile ?? throw new RuntimeException(sprintf(
                'Could not find output file path for "%s"',
                $data->configuration->className,
            )))
            ->map(function (Collection $fileEnums, string $fileName) {
                if ($fileEnums->count() > 1) {
                    throw new RuntimeException(sprintf(
                        'Multiple enums found while generating "%s", only one item per file is supported.',
                        $fileName,
                    ));
                }

                return $fileEnums->firstOrFail();
            });

        $enumGenerator = $this->resolve(EnumGeneratorContract::class);

        return $this->writeOrCheckFiles(
            $enumConfiguration->outputPath,
            $enumsByFile,
            fn(EnumData $enum) => $enumGenerator->generate($enum),
            $returnValue,
        );
    }

    /**
     * @param ParserGeneratorConfiguration $parserConfiguration
     * @param Collection<int, ParserData> $parsers
     * @param int $returnValue
     * @return int
     */
    private function generateParsers(
        ParserGeneratorConfiguration $parserConfiguration,
        Collection $parsers,
        int $returnValue,
    ): int {
        if ($parsers->isEmpty()) {
            return $returnValue;
        }

        if (!$parserConfiguration->outputPath) {
            throw new RuntimeException(sprintf(
                'Found %d %s to generate but no output path was specified.',
                $parsers->count(),
                Str::plural('parser', $parsers->count()),
            ));
        }

        $this->components->info(
            sprintf('Processing %d %s', $parsers->count(), Str::plural('parser', $parsers->count())),
        );

        /**
         * @var Collection<string, ParserData> $parsersByFile
         */
        $parsersByFile = $parsers
            ->collect()
            ->groupBy(fn(ParserData $data) => $data->configuration->parserFile
                ?? throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $data->configuration->method[0],
                    $data->configuration->method[1],
                )))
            ->map(function (Collection $fileParsers, string $fileName) {
                if ($fileParsers->count() > 1) {
                    throw new RuntimeException(sprintf(
                        'Multiple parsers found while generating "%s", only one item per file is supported.',
                        $fileName,
                    ));
                }

                return $fileParsers->firstOrFail();
            });

        $parserGenerator = $this->resolve(ParserGeneratorContract::class);

        return $this->writeOrCheckFiles(
            $parserConfiguration->outputPath,
            $parsersByFile,
            fn(ParserData $parser) => $parserGenerator->generate($parser, $parsers),
            $returnValue,
        );
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param string $outputPath
     * @param Collection<TKey, TValue> $itemsGroupedByFile
     * @param Closure(TValue): string $generateCallback
     * @param int $returnValue
     * @return int
     */
    private function writeOrCheckFiles(
        string $outputPath,
        Collection $itemsGroupedByFile,
        Closure $generateCallback,
        int $returnValue,
    ): int {
        foreach ($itemsGroupedByFile as $fileName => $item) {
            $filePath = $outputPath . '/' . $fileName;

            try {
                $fileContents = $generateCallback($item);
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

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $parameters
     * @return T
     * @noinspection PhpSameParameterValueInspection
     */
    private function resolve(string $class, array $parameters = [])
    {
        return resolve($class, $parameters);
    }
}
