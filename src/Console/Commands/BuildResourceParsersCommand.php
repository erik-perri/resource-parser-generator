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
use ResourceParserGenerator\DataObjects\ParserData;
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

        if ($enums->isNotEmpty()) {
            $this->components->info(
                sprintf('Processing %s %s', $enums->count(), Str::plural('enum', $enums->count())),
            );

            $enumsByFile = $enums->groupBy(fn(EnumData $data) => $data->configuration->enumFile
                ?? throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s"',
                    $data->configuration->className,
                )));

            $enumGenerator = $this->resolve(EnumGeneratorContract::class);

            $returnValue = $this->writeOrCheckFiles(
                $enumConfiguration->outputPath,
                $enumsByFile,
                fn(Collection $localEnums) => $enumGenerator->generate($localEnums),
                $returnValue,
            );
        }

        if ($parsers->isNotEmpty()) {
            $this->components->info(
                sprintf('Processing %s %s', $parsers->count(), Str::plural('parser', $parsers->count())),
            );

            $parsersByFile = $parsers->groupBy(fn(ParserData $data) => $data->configuration->parserFile
                ?? throw new RuntimeException(sprintf(
                    'Could not find output file path for "%s::%s"',
                    $data->configuration->method[0],
                    $data->configuration->method[1],
                )));

            $parserGenerator = $this->resolve(ParserGeneratorContract::class);

            $returnValue = $this->writeOrCheckFiles(
                $parserConfiguration->outputPath,
                $parsersByFile,
                fn(Collection $localParsers) => $parserGenerator->generate($localParsers, $parsers),
                $returnValue,
            );
        }

        return $returnValue;
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
        int $returnValue
    ): int {
        foreach ($itemsGroupedByFile as $fileName => $localItems) {
            $filePath = $outputPath . '/' . $fileName;

            try {
                $fileContents = $generateCallback($localItems);
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
     */
    private function resolve(string $class, array $parameters = [])
    {
        return resolve($class, $parameters);
    }
}
