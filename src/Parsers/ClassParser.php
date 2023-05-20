<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Parsers\PhpFileParserContract;
use ResourceParserGenerator\Parsers\Data\ReflectedClassScope;
use RuntimeException;

class ClassParser implements ClassParserContract
{
    public function __construct(
        private readonly ClassFileLocatorContract $classLocator,
        private readonly PhpFileParserContract $fileParser,
    ) {
        //
    }

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
            $firstEnum = $fileScope->enums()->first();
            if ($firstEnum) {
                return $firstEnum;
            }

            throw new RuntimeException(sprintf('Could not find class "%s" in file "%s"', $className, $classFile));
        }

        return $firstClass;
    }
}
