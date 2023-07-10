<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\DataObjects\ParserData;
use ResourceParserGenerator\Exceptions\ConfigurationParserException;
use ResourceParserGenerator\Parsers\ParserGeneratorConfigurationParser;
use ResourceParserGenerator\Processors\EnumConfigurationProcessor;
use ResourceParserGenerator\Processors\ParserConfigurationProcessor;
use ResourceParserGenerator\Processors\ResourceConfigurationProcessor;
use RuntimeException;
use Throwable;

class BuildResourceParsersCommand extends Command
{
    protected $signature = 'build:resource-parsers {--check} {--enum-config=build.enums} {--parser-config=build.resources}';
    protected $description = 'Generate resource parsers based on the specified configuration.';

    public function handle(): int
    {
        try {
            $parserConfiguration = $this->resolve(ParserGeneratorConfigurationParser::class)->parse(
                strval($this->option('parser-config')),
            );
        } catch (ConfigurationParserException $error) {
            $this->components->error($error->getMessage());
            if ($error->errors) {
                $this->components->bulletList($error->errors);
            }
            return static::FAILURE;
        }

        try {
            $resources = $this->resolve(ResourceConfigurationProcessor::class)->process($parserConfiguration);
            $enums = $this->resolve(EnumConfigurationProcessor::class)->process($parserConfiguration, $resources);
            $parsers = $this->resolve(ParserConfigurationProcessor::class)
                ->process($parserConfiguration, $resources, $enums);

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
            $filePath = $parserConfiguration->outputPath . '/' . $fileName;

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
