<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Commands;

use Illuminate\Console\Command;
use ResourceParserGenerator\Filesystem\ClassFileFinder;
use Throwable;

class GenerateResourceParserCommand extends Command
{
    protected $signature = 'resource-parser:generate {resourceClassName} {formatMethod}';

    protected $description = 'Generate resource parser';

    public function handle(): int
    {
        $className = $this->argument('resourceClassName');
        $formatMethod = $this->argument('formatMethod');

        if (!class_exists($className)) {
            $this->components->error('Class "' . $className . '" does not exist.');
            return static::FAILURE;
        }

        if (!method_exists($className, $formatMethod)) {
            $this->components->error('Class "' . $className . '" does not contain a "' . $formatMethod . '" method.');
            return static::FAILURE;
        }

        try {
            $classFile = $this->getClassFileFinder()->find($className);

            $this->components->info('Found class "' . $className . '" in file "' . $classFile . '"');
            $this->components->info(' - TODO Find "' . $formatMethod . '" and parse returns.');
        } catch (Throwable $error) {
            $this->components->error('Failed to parse class "' . $className . '": ' . $error->getMessage());
            return static::FAILURE;
        }

        return static::SUCCESS;
    }

    private function getClassFileFinder(): ClassFileFinder
    {
        /** @var ClassFileFinder $finder */
        $finder = resolve(ClassFileFinder::class);

        return $finder;
    }
}
