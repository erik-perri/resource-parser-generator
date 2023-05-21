<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ResourceParserGenerator\Contracts\Generators\ParserNameGeneratorContract;
use ResourceParserGenerator\Contracts\Generators\ResourceParserGeneratorContract;
use ResourceParserGenerator\Contracts\Parsers\ResourceParserContract;
use ResourceParserGenerator\Contracts\ResourceParserContextRepositoryContract;
use ResourceParserGenerator\DataObjects\ResourceConfiguration;
use Throwable;

class BuildResourceParsersCommand extends Command
{
    protected $signature = 'build:resource-parsers';
    protected $description = 'Generate resource parsers based on "build.resource_parsers" configuration.';

    public function handle(): int
    {
        // Load and validate the configuration.
        [$configuredResources, $outputPath] = $this->parseConfiguration('build.resource_parsers');
        if ($configuredResources === null) {
            return static::FAILURE;
        }

        // Fill any unspecified values in the configuration using the name generator.
        $configuredResources = $configuredResources->map($this->fillUnspecifiedConfiguration(...));

        // Parse the resources and their dependencies
        $resourceParser = $this->resolve(ResourceParserContract::class);
        foreach ($configuredResources as $config) {
            try {
                $resourceParser->parse($config->className, $config->methodName);
            } catch (Throwable $error) {
                $this->components->error(sprintf(
                    'Failed to generate parser for "%s::%s"',
                    $config->className,
                    $config->methodName,
                ));
                $this->components->bulletList([$error->getMessage()]);
                return static::FAILURE;
            }
        }

        $parserRepository = $this->resolve(ResourceParserContextRepositoryContract::class);
        $parserGenerator = $this->resolve(ResourceParserGeneratorContract::class);

        // Fill the configuration on any dependencies that were not explicitly configured.
        $parserCollection = $parserRepository->updateConfiguration(
            function (ResourceConfiguration $defaultConfig) use ($configuredResources) {
                $configuredResource = $configuredResources->first(fn(ResourceConfiguration $resource) => $resource->is(
                    $defaultConfig->className,
                    $defaultConfig->methodName,
                ));

                return $this->fillUnspecifiedConfiguration($configuredResource ?? $defaultConfig);
            },
        );

        foreach ($parserCollection->splitToFiles() as $fileName => $parsers) {
            $parserRepository->setLocalContext($parsers);

            $filePath = $outputPath . '/' . $fileName;
            $fileContents = $parserGenerator->generate($parsers);

            $this->components->twoColumnDetail(
                sprintf('Writing %s', $filePath),
                sprintf('%s bytes', number_format(strlen($fileContents))),
            );

            File::put($filePath, $fileContents);
        }

        $parserRepository->setLocalContext(collect());

        return static::SUCCESS;
    }

    /**
     * @param string $configKey
     * @return array{0: Collection<int, ResourceConfiguration>|null, 1: string|null}
     * @noinspection PhpSameParameterValueInspection
     */
    private function parseConfiguration(string $configKey): array
    {
        $config = Config::get($configKey);
        if (!$config) {
            $this->components->error(
                sprintf('No configuration found at "%s" for resource parser generation.', $configKey),
            );
            return [null, null];
        }

        $validateCallableArray = function ($key, $value, $fail) {
            if (!is_array($value) || (array_is_list($value) && count($value) !== 2)) {
                $fail(sprintf('The %s field must be a callable array of exactly two items.', $key));
            } elseif (array_is_list($value)) {
                if (!class_exists($value[0])) {
                    $fail(sprintf('The %s field references unknown class "%s".', $key, $value[0]));
                } elseif (!method_exists($value[0], $value[1])) {
                    $fail(sprintf('The %s field references unknown method "%s::%s".', $key, $value[0], $value[1]));
                }
            }
        };

        $validator = Validator::make(
            Arr::wrap($config),
            [
                'output_path' => ['required', 'string'],
                'parsers' => ['required', 'array'],
                'parsers.*' => [
                    'array',
                    $validateCallableArray,
                ],
                'parsers.*.output_file' => [
                    'doesnt_start_with:/',
                    'string',
                ],
                'parsers.*.resource' => [
                    'required_with:parsers.*.output_file',
                    'array',
                    $validateCallableArray,
                ],
                'parsers.*.type' => ['string', 'ascii'],
                'parsers.*.variable' => ['string', 'ascii'],
            ],
            [],
            [
                'output_path' => 'build.resource_parsers.output_path',
                'parsers' => 'build.resource_parsers.parsers',
            ],
        );

        $errors = $validator->errors();

        if (count($errors)) {
            $this->components->error('Errors found in configuration:');
            $this->components->bulletList($errors->all());
            return [null, null];
        }

        $valid = $validator->valid();

        $outputPath = $valid['output_path'];
        if (!File::exists($outputPath)) {
            $this->components->error(sprintf('Output path "%s" does not exist.', $outputPath));
            return [null, null];
        }

        return [
            // @phpstan-ignore-next-line -- The validation rule was a big enough pain, not worth typing this for PHPStan
            collect($valid['parsers'])->map(function ($parser) {
                if (array_is_list($parser)) {
                    return new ResourceConfiguration($parser[0], $parser[1], null, null, null);
                }

                return new ResourceConfiguration(
                    $parser['resource'][0],
                    $parser['resource'][1],
                    $parser['type'] ?? null,
                    $parser['variable'] ?? null,
                    $parser['output_file'] ?? null,
                );
            }),
            $outputPath,
        ];
    }

    private function fillUnspecifiedConfiguration(
        ResourceConfiguration $configuration,
    ): ResourceConfiguration {
        $generator = $this->resolve(ParserNameGeneratorContract::class);

        $file = $configuration->outputFilePath
            ?? $generator->generateFileName($configuration->className);
        $type = $configuration->outputType
            ?? $generator->generateTypeName($configuration->className, $configuration->methodName);
        $variable = $configuration->outputVariable
            ?? $generator->generateVariableName($configuration->className, $configuration->methodName);

        return new ResourceConfiguration(
            $configuration->className,
            $configuration->methodName,
            $type,
            $variable,
            $file,
        );
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
