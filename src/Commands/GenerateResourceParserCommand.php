<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnArrayTypeParser;
use RuntimeException;
use Throwable;

class GenerateResourceParserCommand extends Command
{
    protected $signature = 'resource-parser:generate {resourceClassName} {methodName}';

    protected $description = 'Generate resource parser';

    public function handle(): int
    {
        $className = $this->argument('resourceClassName');
        $methodName = $this->argument('methodName');

        if (!class_exists($className)) {
            $this->components->error('Class "' . $className . '" does not exist.');
            return static::FAILURE;
        }

        if (!method_exists($className, $methodName)) {
            $this->components->error('Class "' . $className . '" does not contain a "' . $methodName . '" method.');
            return static::FAILURE;
        }

        try {
            $classFile = $this->getClassFileFinder()->find($className);

            $returns = $this->getClassMethodReturnArrayTypeParser()->parse($className, $classFile, $methodName);
            $mergedReturns = [];

            foreach ($returns as $returnGroup) {
                foreach ($returnGroup as $property => $returnTypes) {
                    $mergedReturns[$property] = array_unique(array_merge(
                        $mergedReturns[$property] ?? [],
                        $returnTypes
                    ));
                }
            }

            $shortClassName = class_basename($className);
            $parserName = Str::camel($shortClassName) . Str::studly($methodName) . 'Parser';

            $imports = [];
            $shapes = [];

            foreach ($mergedReturns as $property => $returnTypes) {
                [$zodImports, $zodShapes] = $this->getZodType($returnTypes);
                $imports = array_unique(array_merge($imports, $zodImports));

                if (count($zodShapes) > 1) {
                    $imports[] = 'union';
                }

                $shapePart = count($zodShapes) > 1
                    ? 'union(' . implode(', ', $zodShapes) . ')'
                    : $zodShapes[0];

                $shapes[] = "    $property: $shapePart,";
            }

            $imports[] = 'object';
            $imports[] = 'output';
            sort($imports);
            $imports = array_unique($imports);

            $shapeText = implode(PHP_EOL, $shapes);
            $importText = implode(', ', $imports);

            $template = <<<EOT
import {{$importText}} from 'zod';

export const $parserName = object({
{$shapeText}
});

export type $shortClassName = output<typeof $parserName>;

EOT;

            $this->output->write($template);
        } catch (Throwable $error) {
            $this->components->error(
                'Failed to generate parser for "' . $className . '": ' . $error->getMessage(),
            );
            return static::FAILURE;
        }

        return static::SUCCESS;
    }

    private function getZodType(array $phpTypes): array
    {
        $zodImports = [];
        $zodShapes = [];

        foreach ($phpTypes as $phpType) {
            switch ($phpType) {
                case 'string':
                case 'null':
                    $zodImports[] = $phpType;
                    $zodShapes[] = $phpType . '()';
                    break;
                case 'int':
                    $zodImports[] = 'number';
                    $zodShapes[] = 'number()';
                    break;
                default:
                    throw new RuntimeException('Unhandled type "' . $phpType . '"');
            }
        }

        return [$zodImports, $zodShapes];
    }

    private function getClassFileFinder(): ClassFileFinder
    {
        /** @var ClassFileFinder $finder */
        $finder = resolve(ClassFileFinder::class);

        return $finder;
    }

    private function getClassMethodReturnArrayTypeParser(): ClassMethodReturnArrayTypeParser
    {
        /** @var ClassMethodReturnArrayTypeParser $parser */
        $parser = resolve(ClassMethodReturnArrayTypeParser::class);

        return $parser;
    }
}
