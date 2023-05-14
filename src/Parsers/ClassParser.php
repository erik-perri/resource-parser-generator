<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Parsers\Data\ReflectedClassScope;
use RuntimeException;

class ClassParser
{
    public function __construct(
        private readonly ClassFileLocatorContract $classLocator,
        private readonly PhpFileParser $fileParser,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param class-string|null $staticContext
     * @return ClassScopeContract
     */
    public function parse(string $className, string|null $staticContext = null): ClassScopeContract
    {
        if (class_exists($className) && !$this->classLocator->exists($className)) {
            return ReflectedClassScope::create(new ReflectionClass($className));
        }

        $classFile = $this->classLocator->get($className);

        $contents = File::get($classFile);
        $fileScope = $this->fileParser->parse($contents, $staticContext);

        $firstClass = $fileScope->classes()->first();
        if (!$firstClass) {
            throw new RuntimeException(sprintf('Could not find class "%s" in file "%s"', $className, $classFile));
        }

        return $firstClass;
    }
}
