<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use ReflectionException;
use ResourceParserGenerator\Builders\ParserBuilder;
use ResourceParserGenerator\Builders\ParserConstraintBuilder;
use ResourceParserGenerator\Builders\ParserFileBuilder;
use ResourceParserGenerator\Exceptions\ParseResultException;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use ResourceParserGenerator\Parsers\ClassMethodReturnTypeParser;
use RuntimeException;
use Throwable;

class GenerateResourceParserCommand extends Command
{
    protected $signature = 'resource-parser:generate {resourceClassMethodSpec*}';

    protected $description = 'Generate resource parser';

    public function handle(): int
    {
        $methods = $this->methods();

        if (!count($methods)) {
            return static::FAILURE;
        }

        $constraintBuilder = $this->make(ParserConstraintBuilder::class);
        $parserFile = $this->make(ParserFileBuilder::class);

        foreach ($methods as [$className, $methodName]) {
            /**
             * @var class-string $className
             */
            try {
                $this->generateParser($className, $methodName, $constraintBuilder, $parserFile);
            } catch (Throwable $error) {
                $this->components->error(
                    'Failed to generate parser for "' . $className . '::' . $methodName . '": ' . $error->getMessage(),
                );
                return static::FAILURE;
            }
        }

        $this->output->writeln($parserFile->create());

        return static::SUCCESS;
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param ParserConstraintBuilder $constraintBuilder
     * @param ParserFileBuilder $parserFile
     * @return void
     * @throws ParseResultException|ReflectionException
     */
    private function generateParser(
        string $className,
        string $methodName,
        ParserConstraintBuilder $constraintBuilder,
        ParserFileBuilder $parserFile
    ): void {
        $classFile = $this->make(ClassFileFinder::class)->find($className);
        $returns = $this->make(ClassMethodReturnTypeParser::class)->parse($className, $classFile, $methodName);

        foreach ($returns as $return) {
            if (!is_array($return)) {
                throw new RuntimeException(
                    'Non-array return type for "' . $methodName . '" found, cannot build parser.'
                );
            }
        }

        /**
         * Validated in foreach above.
         * @var array<string, string[]> $returns
         */

        $parserBuilder = $this->make(ParserBuilder::class, [
            'className' => $className,
            'methodName' => $methodName,
        ]);

        foreach ($returns as $property => $returnTypes) {
            $constraint = $constraintBuilder->create($returnTypes);

            $parserBuilder->addProperty($property, $constraint);
        }

        $parserFile->addParser($parserBuilder);
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
        $methodSpecs = Arr::wrap($this->argument('resourceClassMethodSpec'));
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
}
