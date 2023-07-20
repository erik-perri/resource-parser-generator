<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ResourceParserGenerator\Contracts\DataObjects\EnumSourceContract;
use ResourceParserGenerator\DataObjects\EnumConfiguration;
use ResourceParserGenerator\DataObjects\EnumGeneratorConfiguration;
use ResourceParserGenerator\Exceptions\ConfigurationParserException;

class EnumGeneratorConfigurationParser
{
    /**
     * @throws ConfigurationParserException
     */
    public function parse(string $configKey): EnumGeneratorConfiguration
    {
        $config = Config::get($configKey);
        if (!$config) {
            throw new ConfigurationParserException(
                sprintf('No configuration found at "%s" for enum generation.', $configKey),
            );
        }

        $validator = Validator::make(
            Arr::wrap($config),
            [
                'output_path' => ['required', 'string'],
                'sources' => ['array'],
                'sources.*' => [
                    function ($key, $value, $fail) {
                        if (!($value instanceof EnumSourceContract)) {
                            $fail(sprintf('The %s field must be an instance of %s.', $key, EnumSourceContract::class));
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
            throw new ConfigurationParserException('Errors found in configuration.', $errors->all());
        }

        $valid = $validator->valid();

        $outputPath = $valid['output_path'];
        if (!File::exists($outputPath)) {
            throw new ConfigurationParserException(sprintf('Output path "%s" does not exist.', $outputPath));
        }

        /**
         * @var Collection<int, EnumConfiguration> $sources
         */
        $sources = collect();

        if (isset($valid['sources'])) {
            foreach ($valid['sources'] as $source) {
                // TODO Add EnumPath?
                if ($source instanceof EnumConfiguration) {
                    if ($source->enumFile !== null && $sources->contains('enumFile', $source->enumFile)) {
                        throw new ConfigurationParserException(sprintf(
                            'Duplicate enum file "%s" configured.',
                            $source->enumFile,
                        ));
                    }
                    $sources->push($source);
                } else {
                    throw new ConfigurationParserException(sprintf('Unhandled source type "%s"', get_class($source)));
                }
            }
        }

        return new EnumGeneratorConfiguration($outputPath, ...$sources->all());
    }
}
