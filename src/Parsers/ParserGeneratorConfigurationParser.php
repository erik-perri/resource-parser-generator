<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ResourceParserGenerator\Contracts\DataObjects\ParserSourceContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFormatLocatorContract;
use ResourceParserGenerator\DataObjects\ParserConfiguration;
use ResourceParserGenerator\DataObjects\ParserGeneratorConfiguration;
use ResourceParserGenerator\DataObjects\ResourcePath;
use ResourceParserGenerator\Exceptions\ConfigurationParserException;
use ResourceParserGenerator\Filesystem\ResourceFileLocator;

class ParserGeneratorConfigurationParser
{
    public function __construct(
        private readonly ResourceFileLocator $resourceFileLocator,
        private readonly ResourceFormatLocatorContract $resourceFileFormatLocator,
    ) {
    }

    /**
     * @throws ConfigurationParserException
     */
    public function parse(string $configKey): ParserGeneratorConfiguration
    {
        $config = Config::get($configKey);
        if (!$config) {
            return new ParserGeneratorConfiguration(null);
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
            throw new ConfigurationParserException('Errors found in configuration.', $errors->all());
        }

        $valid = $validator->valid();

        $outputPath = $valid['output_path'];
        if (!File::exists($outputPath)) {
            throw new ConfigurationParserException(sprintf('Output path "%s" does not exist.', $outputPath));
        }

        /**
         * @var Collection<int, ParserConfiguration> $sources
         */
        $sources = collect();

        if (isset($valid['sources'])) {
            foreach ($valid['sources'] as $source) {
                if ($source instanceof ParserConfiguration) {
                    if ($source->parserFile !== null && $sources->contains('parserFile', $source->parserFile)) {
                        throw new ConfigurationParserException(sprintf(
                            'Duplicate parser file "%s" configured.',
                            $source->parserFile,
                        ));
                    }
                    $sources->push($source);
                } elseif ($source instanceof ResourcePath) {
                    $files = $this->resourceFileLocator->files($source);
                    foreach ($files as $file) {
                        $formats = $this->resourceFileFormatLocator->formatsInFile($file);
                        foreach ($formats as $format) {
                            $sources[] = new ParserConfiguration([$format->className, $format->methodName]);
                        }
                    }
                } else {
                    throw new ConfigurationParserException(sprintf('Unhandled source type "%s"', get_class($source)));
                }
            }
        }

        return new ParserGeneratorConfiguration($outputPath, ...$sources->all());
    }
}
