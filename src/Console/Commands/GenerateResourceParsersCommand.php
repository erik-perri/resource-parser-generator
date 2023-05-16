<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use ResourceParserGenerator\Contracts\Parsers\ResourceParserContract;
use ResourceParserGenerator\Filesystem\Contracts\ParserFileSplitterContract;
use ResourceParserGenerator\Filesystem\NoOpFileSplitter;
use ResourceParserGenerator\Filesystem\SplitByResourceFileSplitter;
use ResourceParserGenerator\Generators\ResourceParserFileGenerator;
use ResourceParserGenerator\Parsers\Data\ResourceParserCollection;
use Throwable;

class GenerateResourceParsersCommand extends Command
{
    protected $signature = 'generate:resource-parsers '
    . '{output-path : Where to put the parser(s). A directory if using a split strategy or a file if not.} '
    . '{resource-class-method-spec* : The resource(s) to make parsers for. Format: "ClassName::methodName"} '
    . '{--split-strategy=resource : How to split the output files. Valid options: resource, none}';

    protected $description = 'Generate resource parsers';

    public function handle(): int
    {
        $methods = $this->methods();
        if (!count($methods)) {
            return static::FAILURE;
        }

        $outputPath = $this->argument('output-path');
        if (!is_string($outputPath)) {
            $this->components->error('Output path must be a string.');
            return static::FAILURE;
        }

        $parserFileSplitter = $this->splitter($outputPath);
        if (!$parserFileSplitter) {
            return static::FAILURE;
        }

        if ($parserFileSplitter instanceof NoOpFileSplitter) {
            $outputPath = dirname($outputPath);
        }

        if (!File::exists($outputPath)) {
            $this->components->error('Output path "' . $outputPath . '" does not exist.');
            return static::FAILURE;
        }

        $parserCollection = $this->make(ResourceParserCollection::class);
        $resourceParser = $this->make(ResourceParserContract::class);
        $parserFileGenerator = $this->make(ResourceParserFileGenerator::class);

        foreach ($methods as [$className, $methodName]) {
            /**
             * @var class-string $className
             */
            try {
                $resourceParser->parse($className, $methodName, $parserCollection);
            } catch (Throwable $error) {
                $this->components->error(
                    'Failed to generate parser for "' . $className . '::' . $methodName . '": ' . $error->getMessage(),
                );
                return static::FAILURE;
            }
        }

        $generatedFiles = $parserFileGenerator->generate($parserCollection, $parserFileSplitter);

        foreach ($generatedFiles as $fileName => $fileContents) {
            $filePath = $outputPath . '/' . $fileName;

            $this->components->twoColumnDetail('Writing file', $filePath);

            File::put($filePath, $fileContents);
        }

        return static::SUCCESS;
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $parameters
     * @return T
     */
    private function make(string $class, array $parameters = [])
    {
        return resolve($class, $parameters);
    }

    /**
     * @return array<string[]>
     */
    private function methods(): array
    {
        /** @var string[] $methodSpecs */
        $methodSpecs = Arr::wrap($this->argument('resource-class-method-spec'));
        $methods = [];

        foreach ($methodSpecs as $methodSpec) {
            [$className, $methodName] = explode('::', $methodSpec);

            if (!class_exists($className)) {
                $this->components->error('Class "' . $className . '" does not exist.');
                return [];
            }

            if (!method_exists($className, $methodName)) {
                $this->components->error('Class "' . $className . '" does not contain a "' . $methodName . '" method.');
                return [];
            }

            $methods[] = [$className, $methodName];
        }

        return $methods;
    }

    private function splitter(string $outputPath): ParserFileSplitterContract|null
    {
        $splitStrategy = $this->option('split-strategy');
        if ($splitStrategy === 'none' && !str_contains($outputPath, '.')) {
            $this->components->error('Output path must be a file if using the "none" split strategy.');
            return null;
        }

        $splitter = match ($splitStrategy) {
            'resource' => $this->make(SplitByResourceFileSplitter::class),
            'none' => $this->make(NoOpFileSplitter::class, ['fileName' => basename($outputPath)]),
            // TODO method?
            default => null,
        };

        if (!$splitter) {
            $this->components->error(sprintf('Invalid split strategy "%s"', $splitStrategy));
            return null;
        }

        return $splitter;
    }
}
