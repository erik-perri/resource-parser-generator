<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Commands;

use Illuminate\Console\Command;
use ResourceParserGenerator\Builders\ParserBuilder;
use ResourceParserGenerator\Builders\ParserConstraintBuilder;
use ResourceParserGenerator\Builders\ParserFileBuilder;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\PhpParser\ClassMethodReturnArrayTypeParser;
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
            $classFile = $this->make(ClassFileFinder::class)->find($className);
            $returns = $this->make(ClassMethodReturnArrayTypeParser::class)->parse($className, $classFile, $methodName);

            $constraintBuilder = $this->make(ParserConstraintBuilder::class);
            $parserFile = $this->make(ParserFileBuilder::class);
            $parserBuilder = $this->make(ParserBuilder::class, [
                'className' => $className,
                'methodName' => $methodName,
            ]);

            foreach ($returns as $property => $returnTypes) {
                $constraint = $constraintBuilder->create($returnTypes);

                $parserBuilder->addProperty($property, $constraint);
            }

            $parserFile->addParser($parserBuilder);

            $this->output->writeln($parserFile->create());
        } catch (Throwable $error) {
            $this->components->error(
                'Failed to generate parser for "' . $className . '": ' . $error->getMessage(),
            );
            return static::FAILURE;
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
}
